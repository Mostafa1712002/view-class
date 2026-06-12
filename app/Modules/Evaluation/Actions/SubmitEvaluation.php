<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use App\Modules\Evaluation\Actions\Concerns\WritesResponses;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Scoring\EvidenceGate;
use App\Modules\Evaluation\Scoring\ScoringStrategyFactory;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 11 + 13 — submit an evaluation.
 *
 * Pipeline: persist responses (exact contract shape) → enforce completeness +
 * mandatory-evidence gates against the FROZEN snapshot → score via the strategy
 * for the form type against the snapshot payload → persist aggregates + breakdown
 * → flip status (completed, or pending_approval if the form requires approval)
 * → notify. After submit the evaluation is locked (re-editable only via reopen).
 *
 * Phase E (#202/#203): in shared_mode, "submit" means "submit MY items", setting
 * their item_status to 'completed' (or 'pending_review' if form requires approval).
 * The overall evaluation status is derived from the set of all item_statuses.
 * The legacy path (shared_mode=0) is completely unchanged.
 */
class SubmitEvaluation
{
    use WritesResponses;

    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /**
     * @param array    $answers       see WritesResponses::syncResponses()
     * @param string[] $userRoleSlugs roles of the current user (shared mode only)
     *
     * @throws ValidationException when locked or completeness/evidence gates fail.
     */
    public function submit(Evaluation $evaluation, array $answers, ?string $generalNotes, int $userId = 0, array $userRoleSlugs = []): Evaluation
    {
        if ($evaluation->status instanceof EvaluationStatus && $evaluation->status->isLocked()) {
            throw ValidationException::withMessages([
                'evaluation' => __('evaluation.execute.errors.locked'),
            ]);
        }

        $evaluation->loadMissing('form');
        $form = $evaluation->form;

        // --- Phase E (#202): Shared mode branch ---
        if ($form && $form->shared_mode) {
            return $this->submitShared($evaluation, $answers, $generalNotes, $userId, $userRoleSlugs, $form);
        }

        // --- Legacy branch (shared_mode=0): untouched ---
        $snapshot = $evaluation->snapshot;
        $payload  = $snapshot->payload ?? [];
        $type     = $form->type ?? FormType::tryFrom((string) ($payload['form']['type'] ?? ''));

        return DB::transaction(function () use ($evaluation, $answers, $generalNotes, $form, $snapshot, $payload, $type) {
            // 1) Persist answers in the exact scorer contract shape.
            $this->syncResponses($evaluation, $payload, $type, $answers);
            $evaluation->load(['responses', 'evidences']);

            // 2) Completeness + mandatory-evidence gates (snapshot-bound).
            $problems = $this->gateProblems($evaluation, $payload, $type, $form);
            if ($problems !== []) {
                throw ValidationException::withMessages(['submit' => $problems]);
            }

            // 3) Score against the frozen payload.
            $result = (new ScoringStrategyFactory())->for($type)->score($evaluation, $payload);

            // 3b) Phase B (#204) — evidence-approval gate.
            // Items that require evidence approval but have no approved evidence yet
            // contribute 0 to the score (flagged gated=true in breakdown). When
            // evidence is later approved, ReviewEvidence re-scores the evaluation.
            $result = EvidenceGate::apply($evaluation, $result);

            // 4) Decide terminal status (Phase 3: completed; approval cycle is Phase 4).
            $requiresApproval = (bool) $form->setting('require_approval', false);
            $status           = $requiresApproval ? EvaluationStatus::PendingApproval : EvaluationStatus::Completed;

            // 5) Persist aggregates + breakdown.
            $evaluation->fill([
                'status'          => $status,
                'total_score'     => $result->total,
                'max_score'       => $result->max,
                'percentage'      => $result->percentage,
                'grade_label'     => $result->gradeLabel,
                'score_breakdown' => $result->toArray(),
                'general_notes'   => $generalNotes,
                'submitted_at'    => now(),
            ]);
            $this->refreshCounters($evaluation);
            $evaluation->save();

            // 6) Notify.
            if ($status === EvaluationStatus::PendingApproval) {
                if ($form->setting('notify_on_submit', false)) {
                    $this->notifier->evaluationSubmitted($evaluation, $this->approverIds($form));
                }
            } elseif ($form->setting('allow_subject_view_results', false)
                && $form->setting('notify_on_result_available', true)) {
                $this->notifier->resultAvailable($evaluation);
            }

            $this->audit->record(
                'submit',
                "تسليم تقييم #{$evaluation->id} — النتيجة {$result->percentage}% ({$result->gradeLabel})",
                $evaluation,
                null,
                $evaluation->toArray()
            );

            return $evaluation->refresh();
        });
    }

    /**
     * Required-completeness + mandatory-evidence problems. Empty array = passable.
     *
     * @return string[]
     */
    private function gateProblems(Evaluation $evaluation, array $payload, FormType $type, $form): array
    {
        $problems  = [];
        $items     = $this->snapshotItems($payload);
        $byItem    = [];   // item_id => response (rubric)
        $byInd     = [];   // indicator_id => response (rating/checklist)
        foreach ($evaluation->responses as $r) {
            if ($r->indicator_id !== null) {
                $byInd[(int) $r->indicator_id] = $r;
            } elseif ($r->item_id !== null) {
                $byItem[(int) $r->item_id] = $r;
            }
        }

        // Evidence presence keyed by node.
        $evItems = [];
        $evInds  = [];
        foreach ($evaluation->evidences as $e) {
            if ($e->indicator_id !== null) {
                $evInds[(int) $e->indicator_id] = true;
            } elseif ($e->item_id !== null) {
                $evItems[(int) $e->item_id] = true;
            }
        }

        $requireAllIndicators = (bool) $form->setting('require_all_indicators', false);

        foreach ($items as $itemId => $item) {
            if ($type === FormType::Rubric) {
                $answered = isset($byItem[$itemId]) && $byItem[$itemId]->level_id !== null;
                if ($item['is_required'] && !$answered) {
                    $problems[] = __('evaluation.execute.gate.item_required', ['item' => $item['name'] ?? ('#'.$itemId)]);
                }
                // Mandatory evidence is attached at item level for rubric.
                if ($item['evidence_required'] && empty($evItems[$itemId])) {
                    $problems[] = __('evaluation.execute.gate.item_evidence', ['item' => $item['name'] ?? ('#'.$itemId)]);
                }
            } else {
                foreach ($item['indicators'] as $indicatorId => $indicator) {
                    $indRequired = (bool) ($indicator['is_required'] ?? false) || $requireAllIndicators;
                    $answered    = $type === FormType::Checklist
                        ? array_key_exists($indicatorId, $byInd)
                        : (isset($byInd[$indicatorId]) && $byInd[$indicatorId]->level_id !== null);

                    if ($indRequired && !$answered) {
                        $problems[] = __('evaluation.execute.gate.indicator_required', [
                            'indicator' => $indicator['text'] ?? ('#'.$indicatorId),
                        ]);
                    }
                    if (!empty($indicator['evidence_required']) && empty($evInds[$indicatorId])) {
                        $problems[] = __('evaluation.execute.gate.indicator_evidence', [
                            'indicator' => $indicator['text'] ?? ('#'.$indicatorId),
                        ]);
                    }
                }
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * Phase E (#202/#203) — Shared-mode submit.
     *
     * Writes the current user's items with item_status='completed' (or 'pending_review'
     * if the form requires approval). Other users' responses are preserved.
     * After writing, recomputes the full score and derives the aggregate status.
     */
    private function submitShared(
        Evaluation $evaluation,
        array $answers,
        ?string $generalNotes,
        int $userId,
        array $userRoleSlugs,
        \App\Models\EvaluationForm $form
    ): Evaluation {
        $evaluation->loadMissing('snapshot');
        $snapshot = $evaluation->snapshot;
        $payload  = $snapshot->payload ?? [];
        $type     = $form->type ?? FormType::tryFrom((string) ($payload['form']['type'] ?? ''));

        return DB::transaction(function () use ($evaluation, $answers, $generalNotes, $userId, $userRoleSlugs, $form, $payload, $type) {
            // Resolve item IDs this user is responsible for.
            $responsibleItemIds = $this->responsibleItemIdsFromPayload($payload, $userRoleSlugs);

            // Per-item lock check (#203): items already approved/pending_review are locked.
            $evaluation->loadMissing('responses');
            $lockedItems = $evaluation->responses
                ->whereIn('item_id', $responsibleItemIds)
                ->whereIn('item_status', ['pending_review', 'approved'])
                ->pluck('item_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (!empty($lockedItems)) {
                throw ValidationException::withMessages([
                    'items' => __('evaluation.execute.errors.item_locked'),
                ]);
            }

            // Delete only the current user's items so we can re-insert with updated status.
            if (!empty($responsibleItemIds)) {
                $evaluation->responses()
                    ->whereIn('item_id', $responsibleItemIds)
                    ->delete();
            }

            // Determine terminal item_status for submitted items.
            $requiresApproval = (bool) $form->setting('require_approval', false);
            $itemStatus       = $requiresApproval ? 'pending_review' : 'completed';

            // Write items with terminal status + timestamps.
            $this->syncSharedSubmitResponses(
                $evaluation, $payload, $type, $answers, $responsibleItemIds,
                $userId, $itemStatus
            );

            // Reload ALL responses for scoring.
            $evaluation->load(['responses', 'evidences']);

            // Score against full response set (unchanged scorer).
            $result = (new ScoringStrategyFactory())->for($type)->score($evaluation, $payload);
            $result = EvidenceGate::apply($evaluation, $result);

            // Derive aggregate evaluation status from item_status set.
            $aggregateStatus = $this->deriveAggregateStatusFromSet(
                $evaluation->responses->pluck('item_status')->unique()->values()->all(),
                $requiresApproval
            );

            $evaluation->fill([
                'status'          => $aggregateStatus,
                'total_score'     => $result->total,
                'max_score'       => $result->max,
                'percentage'      => $result->percentage,
                'grade_label'     => $result->gradeLabel,
                'score_breakdown' => $result->toArray(),
                'general_notes'   => $generalNotes,
            ]);
            $this->refreshCounters($evaluation);
            $evaluation->save();

            $this->audit->record(
                'submit_shared',
                "تسليم بنود مشتركة — تقييم #{$evaluation->id} — المستخدم #{$userId} — النتيجة {$result->percentage}% ({$result->gradeLabel})",
                $evaluation,
                null,
                $evaluation->toArray()
            );

            return $evaluation->refresh();
        });
    }

    /**
     * Phase E — Write the current user's responses with the terminal item_status.
     * Mirrors syncSharedResponses in SaveEvaluationDraft but sets item_status + submitted_at.
     */
    private function syncSharedSubmitResponses(
        Evaluation $evaluation,
        array $payload,
        FormType $type,
        array $answers,
        array $responsibleItemIds,
        int $userId,
        string $itemStatus
    ): void {
        if (empty($responsibleItemIds)) {
            return;
        }

        $responsibleSet = array_flip($responsibleItemIds);
        $items          = $this->snapshotItems($payload);
        $levelIds       = $this->snapshotLevelIds($payload);
        $itemNotes      = is_array($answers['item_notes'] ?? null) ? $answers['item_notes'] : [];

        $itemRoleMap = [];
        foreach (($payload['items'] ?? []) as $item) {
            if (is_array($item) && isset($item['id'])) {
                $itemRoleMap[(int) $item['id']] = $item['responsible_role'] ?? null;
            }
        }

        $rows = [];

        if ($type === FormType::Rubric) {
            $chosen = is_array($answers['items'] ?? null) ? $answers['items'] : [];
            foreach ($items as $itemId => $item) {
                if (!isset($responsibleSet[$itemId])) {
                    continue;
                }
                $levelId = isset($chosen[$itemId]) ? (int) $chosen[$itemId] : null;
                $note    = $this->snapshotNoteFromAnswers($itemNotes, $itemId);
                if ($levelId === null && $note === null) {
                    continue;
                }
                $rows[] = [
                    'item_id'          => $itemId,
                    'indicator_id'     => null,
                    'level_id'         => $levelId !== null && in_array($levelId, $levelIds, true) ? $levelId : null,
                    'note'             => $note,
                    'filled_by'        => $userId,
                    'responsible_role' => $itemRoleMap[$itemId] ?? null,
                    'item_status'      => $itemStatus,
                    'submitted_at'     => now(),
                ];
            }
        } else {
            $chosen = is_array($answers['indicators'] ?? null) ? $answers['indicators'] : [];
            foreach ($items as $itemId => $item) {
                if (!isset($responsibleSet[$itemId])) {
                    continue;
                }
                foreach ($item['indicators'] as $indicatorId => $indicator) {
                    if (!array_key_exists($indicatorId, $chosen)) {
                        continue;
                    }
                    $raw = $chosen[$indicatorId];
                    if ($type === FormType::Checklist) {
                        $rows[] = [
                            'item_id'          => $itemId,
                            'indicator_id'     => $indicatorId,
                            'level_id'         => null,
                            'checklist_value'  => (bool) ((int) $raw === 1 || $raw === '1' || $raw === true),
                            'filled_by'        => $userId,
                            'responsible_role' => $itemRoleMap[$itemId] ?? null,
                            'item_status'      => $itemStatus,
                            'submitted_at'     => now(),
                        ];
                    } else {
                        $levelId = (int) $raw;
                        if ($levelId <= 0 || !in_array($levelId, $levelIds, true)) {
                            continue;
                        }
                        $rows[] = [
                            'item_id'          => $itemId,
                            'indicator_id'     => $indicatorId,
                            'level_id'         => $levelId,
                            'filled_by'        => $userId,
                            'responsible_role' => $itemRoleMap[$itemId] ?? null,
                            'item_status'      => $itemStatus,
                            'submitted_at'     => now(),
                        ];
                    }
                }
            }
        }

        foreach ($rows as $row) {
            EvaluationResponse::create(array_merge(['evaluation_id' => $evaluation->id], $row));
        }
    }

    /**
     * Phase E — Derive evaluation-level aggregate status from item_status set.
     */
    private function deriveAggregateStatusFromSet(array $statuses, bool $requiresApproval): EvaluationStatus
    {
        if (empty($statuses)) {
            return EvaluationStatus::Draft;
        }

        if (in_array('pending_review', $statuses, true)) {
            return EvaluationStatus::PendingApproval;
        }

        $hasAnyDraft = in_array('draft', $statuses, true);

        if (!$hasAnyDraft && !in_array('rejected', $statuses, true)) {
            // All items are completed or approved.
            return EvaluationStatus::Completed;
        }

        return EvaluationStatus::Draft;
    }

    /**
     * Phase E — Item IDs from snapshot assigned to one of the given roles.
     *
     * @param  string[] $userRoleSlugs
     * @return int[]
     */
    private function responsibleItemIdsFromPayload(array $payload, array $userRoleSlugs): array
    {
        $ids = [];
        foreach (($payload['items'] ?? []) as $item) {
            if (!is_array($item) || ($item['status'] ?? 'active') === 'disabled') {
                continue;
            }
            $responsible = $item['responsible_role'] ?? null;
            if ($responsible !== null && in_array($responsible, $userRoleSlugs, true)) {
                $ids[] = (int) $item['id'];
            }
        }

        return $ids;
    }

    /** @param array<int|string,mixed> $notes */
    private function snapshotNoteFromAnswers(array $notes, int $itemId): ?string
    {
        $val = $notes[$itemId] ?? null;
        $val = is_string($val) ? trim($val) : null;

        return ($val === null || $val === '') ? null : $val;
    }

    /** Approver candidates for the form's school (Phase 3 placeholder until Task 14). */
    private function approverIds($form): array
    {
        return \App\Models\User::query()
            ->whereHas('roles', fn ($r) => $r->whereIn('slug', ['super-admin', 'school-admin']))
            ->when($form->school_id !== null, fn ($q) => $q->where('users.school_id', $form->school_id))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Recompute progress counters (mirrors SaveEvaluationDraft). */
    private function refreshCounters(Evaluation $evaluation): void
    {
        $itemIds      = $evaluation->responses->whereNull('indicator_id')->pluck('item_id')->filter()->unique();
        $indicatorIds = $evaluation->responses->whereNotNull('indicator_id')->pluck('indicator_id')->unique();

        $itemsAnswered = $itemIds->count() ?: $evaluation->responses
            ->whereNotNull('indicator_id')->pluck('item_id')->filter()->unique()->count();

        $evaluation->items_completed      = $itemsAnswered;
        $evaluation->indicators_completed = $indicatorIds->count();
        $evaluation->evidence_count       = $evaluation->evidences->count();
    }
}
