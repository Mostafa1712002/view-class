<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\Notification;
use App\Modules\Evaluation\Actions\ApproveEvaluation;
use App\Modules\Evaluation\Actions\RejectEvaluation;
use App\Modules\Evaluation\Actions\ReopenEvaluation;
use App\Modules\Evaluation\Actions\RequestReview;
use App\Modules\Evaluation\Actions\SaveEvaluationDraft;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Permissions\EvaluationPermissions;
use App\Modules\Evaluation\Scoring\EvidenceGate;
use App\Modules\Evaluation\Scoring\ScoringStrategyFactory;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Task 14 — approval & review cycle for submitted evaluations.
 *
 * Routes use {evaluation} model binding, so school scope is enforced manually
 * here (super-admin may act cross-school). Each transition lives in its own
 * Action; the controller only coordinates the HTTP concern and surfaces
 * ValidationException as redirect-back errors.
 */
class EvaluationApprovalController extends Controller
{
    use HasSchoolScope;

    /** Statuses that make up the approver's queue. */
    private const QUEUE = ['completed', 'pending_approval', 'needs_review'];

    public function __construct(
        private readonly ApproveEvaluation $approve,
        private readonly RejectEvaluation $reject,
        private readonly RequestReview $review,
        private readonly ReopenEvaluation $reopen,
        private readonly AuditTrail $audit,
    ) {
    }

    /** Approval queue list (filters + KPIs). */
    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $status = $request->string('status')->toString() ?: null;
        $formId = $request->integer('form') ?: null;
        $q      = trim((string) $request->get('q', '')) ?: null;
        $pctMin = $request->filled('pct_min') ? (float) $request->get('pct_min') : null;
        $pctMax = $request->filled('pct_max') ? (float) $request->get('pct_max') : null;

        $evaluations = Evaluation::query()
            ->with(['form:id,title', 'evaluator:id,name', 'subject:id,name'])
            // Per-row analytical counts (#207): answered items, evidence, and the
            // items still awaiting review.
            ->withCount([
                'responses as answered_count',
                'evidences as evidence_count',
                'responses as pending_review_count' => fn (Builder $q) => $q->where('item_status', 'pending_review'),
            ])
            ->whereIn('status', self::QUEUE)
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($status !== null, fn (Builder $q) => $q->where('status', $status))
            ->when($formId !== null, fn (Builder $q) => $q->where('form_id', $formId))
            // Filter by the evaluated teacher's name.
            ->when($q !== null, fn (Builder $w) => $w->whereHas('subject', fn (Builder $s) => $s->where('name', 'like', '%'.$q.'%')))
            // Final-percentage range.
            ->when($pctMin !== null, fn (Builder $w) => $w->where('percentage', '>=', $pctMin))
            ->when($pctMax !== null, fn (Builder $w) => $w->where('percentage', '<=', $pctMax))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.evaluation.approvals.index', [
            'evaluations' => $evaluations,
            'filters'     => ['status' => $status, 'form' => $formId, 'q' => $q, 'pct_min' => $pctMin, 'pct_max' => $pctMax],
            'stats'       => $this->stats($schoolId),
            'statuses'    => $this->queueStatusOptions(),
            'forms'       => $this->scopedForms($schoolId),
        ]);
    }

    /** Read-only detail with approve / reject / request-review / reopen actions. */
    public function show(Evaluation $evaluation): View|RedirectResponse
    {
        $this->authorizeScope($evaluation);

        $evaluation->load([
            'form', 'snapshot', 'evaluator:id,name', 'subject:id,name',
            'approver:id,name', 'responses', 'evidences',
        ]);

        $payload = $evaluation->snapshot?->payload ?? [];

        return view('admin.evaluation.approvals.show', [
            'evaluation'  => $evaluation,
            'form'        => $evaluation->form,
            'payload'     => $payload,
            'type'        => $evaluation->form?->type?->value ?? ($payload['form']['type'] ?? null),
            'responses'   => $this->indexResponses($evaluation),
            'evidences'   => $this->indexEvidences($evaluation),
            'reviewNotes' => $this->latestReviewNotes($evaluation),
            'canReopen'   => $this->canReopen(),
        ]);
    }

    public function approve(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeScope($evaluation);

        try {
            $this->approve->execute($evaluation, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.approved'));
    }

    public function reject(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeScope($evaluation);

        try {
            $this->reject->execute($evaluation, (int) auth()->id(), $request->input('rejection_reason'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.rejected'));
    }

    public function requestReview(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeScope($evaluation);

        $itemIds = (array) $request->input('review_items', []);

        try {
            $this->review->execute($evaluation, (int) auth()->id(), $request->input('review_notes'), $itemIds);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.reviewed'));
    }

    public function reopen(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeScope($evaluation);

        if (!$this->canReopen()) {
            return back()->withErrors(['approval' => __('eval_approval.errors.reopen_forbidden')]);
        }

        try {
            $this->reopen->execute($evaluation, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.approvals.index')
            ->with('status', __('eval_approval.flash.reopened'));
    }

    /* --------------------------------------------------------------- Phase E: Per-item approve/reject/return (#203) */

    /**
     * Phase E (#203) — Approve a single item response in a shared evaluation.
     *
     * Only applies to shared_mode evaluations. Requires eval.approve_item permission.
     * Sets item_status → 'approved', stamps approved_by + approved_at, recomputes score.
     */
    public function approveItem(Request $request, Evaluation $evaluation, int $responseId): RedirectResponse
    {
        $this->authorizeScope($evaluation);
        $this->requireSharedMode($evaluation);
        $this->requirePermission(EvaluationPermissions::APPROVE_ITEM);

        $response = EvaluationResponse::query()
            ->where('evaluation_id', $evaluation->id)
            ->where('id', $responseId)
            ->firstOrFail();

        if ($response->item_status !== 'pending_review') {
            return back()->withErrors(['item' => __('eval_approval.errors.item_not_pending')]);
        }

        $response->update([
            'item_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reject_reason' => null,
        ]);

        $this->recomputeSharedScore($evaluation);

        $this->audit->record(
            'approve_item',
            "اعتماد بند #{$responseId} في التقييم المشترك #{$evaluation->id}",
            $evaluation
        );

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.item_approved', [], 'تم اعتماد البند'));
    }

    /**
     * Phase E (#203) — Reject a single item response.
     *
     * Sets item_status → 'rejected', records reject_reason, recomputes score.
     * The filler will see it as 'rejected' with the reason and can re-submit.
     */
    public function rejectItem(Request $request, Evaluation $evaluation, int $responseId): RedirectResponse
    {
        $this->authorizeScope($evaluation);
        $this->requireSharedMode($evaluation);
        $this->requirePermission(EvaluationPermissions::REJECT_ITEM);

        $response = EvaluationResponse::query()
            ->where('evaluation_id', $evaluation->id)
            ->where('id', $responseId)
            ->firstOrFail();

        if (!in_array($response->item_status, ['pending_review', 'completed'], true)) {
            return back()->withErrors(['item' => __('eval_approval.errors.item_not_pending')]);
        }

        $request->validate(['reject_reason' => ['nullable', 'string', 'max:1000']]);

        $response->update([
            'item_status'   => 'rejected',
            'reject_reason' => $request->input('reject_reason'),
            'approved_by'   => null,
            'approved_at'   => null,
        ]);

        $this->recomputeSharedScore($evaluation);

        $this->audit->record(
            'reject_item',
            "رفض بند #{$responseId} في التقييم المشترك #{$evaluation->id}",
            $evaluation
        );

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.item_rejected', [], 'تم رفض البند'));
    }

    /**
     * Phase E (#203) — Return (send back) a single item to draft for re-evaluation.
     *
     * Sets item_status → 'draft' (unlocks for the filler), stores reject_reason as context note.
     */
    public function returnItem(Request $request, Evaluation $evaluation, int $responseId): RedirectResponse
    {
        $this->authorizeScope($evaluation);
        $this->requireSharedMode($evaluation);
        $this->requirePermission(EvaluationPermissions::RETURN_ITEM);

        $response = EvaluationResponse::query()
            ->where('evaluation_id', $evaluation->id)
            ->where('id', $responseId)
            ->firstOrFail();

        $request->validate(['reject_reason' => ['nullable', 'string', 'max:1000']]);

        $response->update([
            'item_status'   => 'draft',
            'reject_reason' => $request->input('reject_reason'),
            'submitted_at'  => null,
            'approved_by'   => null,
            'approved_at'   => null,
        ]);

        $this->recomputeSharedScore($evaluation);

        $this->audit->record(
            'return_item',
            "إعادة بند #{$responseId} للمقيّم في التقييم المشترك #{$evaluation->id}",
            $evaluation
        );

        return redirect()->route('admin.evaluations.approvals.show', $evaluation->id)
            ->with('status', __('eval_approval.flash.item_returned', [], 'تم إعادة البند للمقيّم'));
    }

    /* --------------------------------------------------------------- Phase E helpers */

    /** Abort with 422 when the evaluation is not in shared mode. */
    private function requireSharedMode(Evaluation $evaluation): void
    {
        $evaluation->loadMissing('form');
        if (!$evaluation->form || !$evaluation->form->shared_mode) {
            abort(422, 'Per-item operations are only available for shared-mode evaluations.');
        }
    }

    /** Check a granular permission via User::canEval() (falls back gracefully). */
    private function requirePermission(string $permission): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $canCheck = method_exists($user, 'canEval') ? $user->canEval($permission) : $user->isSuperAdmin();
        if (!$canCheck) {
            abort(403, "Missing permission: {$permission}");
        }
    }

    /**
     * Recompute the shared evaluation's score and aggregate status from all item responses.
     * Called after every per-item state transition.
     */
    private function recomputeSharedScore(Evaluation $evaluation): void
    {
        $evaluation->load(['responses', 'evidences', 'snapshot', 'form']);
        $payload = $evaluation->snapshot?->payload ?? [];
        $form    = $evaluation->form;
        $type    = $form?->type ?? FormType::tryFrom((string) ($payload['form']['type'] ?? ''));

        if (!$type) {
            return;
        }

        $result = (new ScoringStrategyFactory())->for($type)->score($evaluation, $payload);
        $result = EvidenceGate::apply($evaluation, $result);

        $statuses        = $evaluation->responses->pluck('item_status')->unique()->values()->all();
        $aggregateStatus = $this->deriveAggregateStatusFromSet($statuses);

        $evaluation->fill([
            'status'          => $aggregateStatus,
            'total_score'     => $result->total,
            'max_score'       => $result->max,
            'percentage'      => $result->percentage,
            'grade_label'     => $result->gradeLabel,
            'score_breakdown' => $result->toArray(),
        ]);
        $evaluation->save();
    }

    /**
     * Phase E — Derive evaluation-level aggregate status from item_status set.
     * Mirrors the logic in SaveEvaluationDraft::deriveAggregateStatus.
     */
    private function deriveAggregateStatusFromSet(array $statuses): EvaluationStatus
    {
        if (empty($statuses)) {
            return EvaluationStatus::Draft;
        }

        if (in_array('pending_review', $statuses, true)) {
            return EvaluationStatus::PendingApproval;
        }

        $hasAnyDraft    = in_array('draft', $statuses, true);
        $hasAnyRejected = in_array('rejected', $statuses, true);

        if (!$hasAnyDraft && !$hasAnyRejected) {
            return EvaluationStatus::Completed;
        }

        return EvaluationStatus::Draft;
    }

    /* --------------------------------------------------------------- helpers */

    /** Enforce school scope on a model-bound evaluation. */
    private function authorizeScope(Evaluation $evaluation): void
    {
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin() && (int) $evaluation->school_id !== (int) $this->activeSchoolId()) {
            abort(403);
        }
    }

    /** Reopen is restricted to super-admin / school-admin. */
    private function canReopen(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
    }

    /** KPI counts within scope. */
    private function stats(?int $schoolId): array
    {
        $base = fn (): Builder => Evaluation::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId));

        // Performance aggregates over evaluations that have a computed score
        // (completed/approved). Scoped to the same school boundary.
        $scored = (clone $base())->whereIn('status', [
            EvaluationStatus::Completed->value,
            EvaluationStatus::Approved->value,
        ]);

        // Item/evidence backlog counts, joined through evaluations to honour scope.
        $itemsPendingReview = \App\Models\EvaluationResponse::query()
            ->where('item_status', 'pending_review')
            ->whereHas('evaluation', fn (Builder $q) => $q->when($schoolId !== null, fn ($w) => $w->where('school_id', $schoolId)))
            ->distinct()
            ->count('item_id');

        $evidencePending = \App\Models\EvaluationEvidence::query()
            ->where('status', '!=', 'approved')
            ->whereHas('evaluation', fn (Builder $q) => $q->when($schoolId !== null, fn ($w) => $w->where('school_id', $schoolId)))
            ->count();

        return [
            'pending'        => (clone $base())->where('status', EvaluationStatus::PendingApproval->value)->count(),
            'completed'      => (clone $base())->where('status', EvaluationStatus::Completed->value)->count(),
            'needs_review'   => (clone $base())->where('status', EvaluationStatus::NeedsReview->value)->count(),
            'approved'       => (clone $base())->where('status', EvaluationStatus::Approved->value)->count(),
            // Analytical metrics (#207)
            'teachers'       => (int) (clone $base())->where('subject_type', 'user')->distinct()->count('subject_id'),
            'avg_performance'=> round((float) (clone $scored)->avg('percentage'), 1),
            'max_pct'        => round((float) (clone $scored)->max('percentage'), 1),
            'min_pct'        => round((float) (clone $scored)->min('percentage'), 1),
            'items_pending_review' => $itemsPendingReview,
            'evidence_pending'     => $evidencePending,
        ];
    }

    /** Status filter options limited to the queue statuses. */
    private function queueStatusOptions(): array
    {
        $out = [];
        foreach (self::QUEUE as $value) {
            $out[$value] = EvaluationStatus::from($value)->label();
        }

        return $out;
    }

    /** Forms within scope (for the filter dropdown). */
    private function scopedForms(?int $schoolId): \Illuminate\Support\Collection
    {
        return EvaluationForm::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where(
                fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')
            ))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    /** Latest review notes/items recorded for this evaluation, if any. */
    private function latestReviewNotes(Evaluation $evaluation): ?array
    {
        $n = Notification::query()
            ->where('type', 'evaluation_review')
            ->where('user_id', $evaluation->evaluator_id)
            ->orderByDesc('id')
            ->get()
            ->first(fn ($row) => (int) data_get($row->data, 'evaluation_id') === (int) $evaluation->id);

        if (!$n) {
            return null;
        }

        return [
            'notes' => (string) data_get($n->data, 'review_notes', ''),
            'items' => (array) data_get($n->data, 'review_items', []),
        ];
    }

    /** Map responses for the read-only view: item_id => levelId; indicator_id => levelId|bool. */
    private function indexResponses(Evaluation $evaluation): array
    {
        $byItem = [];
        $byInd  = [];
        $notes  = [];
        foreach ($evaluation->responses as $r) {
            if ($r->indicator_id !== null) {
                $byInd[(int) $r->indicator_id] = $r->checklist_value !== null
                    ? (bool) $r->checklist_value
                    : ($r->level_id !== null ? (int) $r->level_id : null);
            } elseif ($r->item_id !== null) {
                $byItem[(int) $r->item_id] = $r->level_id !== null ? (int) $r->level_id : null;
                if ($r->note !== null) {
                    $notes[(int) $r->item_id] = $r->note;
                }
            }
        }

        return ['items' => $byItem, 'indicators' => $byInd, 'notes' => $notes];
    }

    /** Evidence grouped by node: 'item:ID' / 'ind:ID' => [evidence...]. */
    private function indexEvidences(Evaluation $evaluation): array
    {
        $out = [];
        foreach ($evaluation->evidences as $e) {
            $key = $e->indicator_id !== null ? 'ind:'.$e->indicator_id : 'item:'.$e->item_id;
            $out[$key][] = $e;
        }

        return $out;
    }
}
