<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Modules\Evaluation\Actions\ReviewEvidence;
use App\Modules\Evaluation\Actions\UploadEvidence;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Task 12 — attach / remove evidence (files or links) bound to a specific
 * item or indicator of an evaluation, from the execution screen.
 *
 * Phase B (#204) — adds approve / reject / request-edit review endpoints.
 */
class EvaluationEvidenceController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly UploadEvidence $evidence,
        private readonly ReviewEvidence $review,
    ) {
    }

    public function store(Request $request, int $evaluation): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        $data = $request->validate([
            // Phase B: extended accepted types (backward-compatible — old file|link still valid)
            'type'               => ['required', 'in:file,image,pdf,link,document,system,auto_platform'],
            'item_id'            => ['nullable', 'integer'],
            'indicator_id'       => ['nullable', 'integer'],
            'url'                => ['nullable', 'url', 'max:2048'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'visible_to_subject' => ['nullable', 'boolean'],
            'file'               => ['nullable', 'file', 'max:51200'], // 50MB
        ]);

        try {
            $this->evidence->upload(
                $eval,
                [
                    'type'               => $data['type'],
                    'item_id'            => $data['item_id'] ?? null,
                    'indicator_id'       => $data['indicator_id'] ?? null,
                    'url'                => $data['url'] ?? null,
                    'description'        => $data['description'] ?? null,
                    'visible_to_subject' => $request->boolean('visible_to_subject'),
                ],
                $request->file('file'),
                (int) auth()->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.evidence.flash.added'));
    }

    public function destroy(int $evaluation, int $evidence): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        $row = EvaluationEvidence::query()
            ->where('evaluation_id', $eval->id)
            ->whereKey($evidence)
            ->first();

        if (!$row) {
            return back()->with('error', __('evaluation.evidence.errors.not_found'));
        }

        $canOverride = (bool) (auth()->user()?->isSuperAdmin());

        try {
            $this->evidence->delete($eval, $row, (int) auth()->id(), $canOverride);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.evidence.flash.removed'));
    }

    // -----------------------------------------------------------------------
    // Phase B (#204) — evidence review endpoints
    // TODO Phase D: granular evidence permissions (upload/approve/reject/delete evidence)
    // -----------------------------------------------------------------------

    /** Approve a specific evidence record and re-score the parent evaluation. */
    public function approve(Request $request, EvaluationEvidence $evidence): RedirectResponse
    {
        return $this->runReview($evidence, 'approved', null);
    }

    /** Reject a specific evidence record (requires a reason note). */
    public function reject(Request $request, EvaluationEvidence $evidence): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        return $this->runReview($evidence, 'rejected', $data['note']);
    }

    /** Flag evidence as needing edits from the uploader. */
    public function requestEdit(Request $request, EvaluationEvidence $evidence): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->runReview($evidence, 'needs_edit', $data['note'] ?? null);
    }

    private function runReview(EvaluationEvidence $evidence, string $decision, ?string $note): RedirectResponse
    {
        // Multi-tenant guard: the route binds the evidence by id, so verify its
        // parent evaluation belongs to the reviewer's active school before acting.
        // A super-admin (activeSchoolId() === null) is unscoped and may review any.
        $scopeSchoolId = $this->activeSchoolId();
        $evaluationSchoolId = (int) optional($evidence->evaluation)->school_id;
        if ($scopeSchoolId !== null && $evaluationSchoolId !== (int) $scopeSchoolId) {
            abort(403);
        }

        // Phase D (#208/#210): granular permission gate (super-admin + school-admin
        // hold these by default, so existing access is preserved).
        $perm = match ($decision) {
            'approved'   => \App\Modules\Evaluation\Permissions\EvaluationPermissions::APPROVE_EVIDENCE,
            'rejected'   => \App\Modules\Evaluation\Permissions\EvaluationPermissions::REJECT_EVIDENCE,
            default      => \App\Modules\Evaluation\Permissions\EvaluationPermissions::APPROVE_EVIDENCE,
        };
        abort_unless(auth()->user()?->canEval($perm), 403);

        try {
            $this->review->execute($evidence, $decision, $note, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $flashKey = match ($decision) {
            'approved'   => __('evaluation.evidence_approve_flash'),
            'rejected'   => __('evaluation.evidence_reject_flash'),
            'needs_edit' => __('evaluation.evidence_request_edit_flash'),
            default      => __('evaluation.evidence.flash.added'),
        };

        $evalId = $evidence->evaluation_id;

        return redirect()->route('admin.evaluations.execute.show', $evalId)
            ->with('status', $flashKey);
    }

    private function resolveMine(int $id): Evaluation|RedirectResponse
    {
        $eval = Evaluation::query()->whereKey($id)->first();
        if (!$eval || (int) $eval->evaluator_id !== (int) auth()->id()) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.execute.errors.not_yours'));
        }

        return $eval;
    }
}
