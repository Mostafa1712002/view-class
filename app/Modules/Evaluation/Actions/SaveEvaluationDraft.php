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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 11 — save an evaluation as a draft. No completeness checks; the evaluator
 * may save partial answers at any time. Locked evaluations cannot be drafted.
 *
 * Phase E (#202/#203): in shared_mode the save path writes ONLY the current user's
 * items (scoped by responsible_role), preserves other users' responses, recomputes
 * the full score from all responses, and updates the evaluation's aggregate status.
 * The legacy path (shared_mode=0) is completely unchanged.
 */
class SaveEvaluationDraft
{
    use WritesResponses;

    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * @param array       $answers see WritesResponses::syncResponses()
     * @param string[]    $userRoleSlugs roles of the current user (shared mode only)
     *
     * @throws ValidationException when the evaluation is locked or item is locked (#203).
     */
    public function save(Evaluation $evaluation, array $answers, ?string $generalNotes, int $userId = 0, array $userRoleSlugs = []): Evaluation
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
            return $this->saveShared($evaluation, $answers, $generalNotes, $userId, $userRoleSlugs, $form);
        }

        // --- Legacy branch (shared_mode=0): untouched ---
        $snapshot = $evaluation->snapshot;
        $type     = $form->type ?? FormType::tryFrom((string) ($snapshot->payload['form']['type'] ?? ''));

        return DB::transaction(function () use ($evaluation, $answers, $generalNotes, $snapshot, $type) {
            $this->syncResponses($evaluation, $snapshot->payload ?? [], $type, $answers);

            $evaluation->status        = EvaluationStatus::Draft;
            $evaluation->general_notes = $generalNotes;
            $this->refreshCounters($evaluation);
            $evaluation->save();

            $this->audit->record(
                'draft',
                "حفظ مسودة تقييم #{$evaluation->id}",
                $evaluation
            );

            return $evaluation->refresh();
        });
    }

    /**
     * Phase E (#202/#203) — Shared-mode draft save.
     *
     * Writes ONLY the current user's item responses (those whose responsible_role matches
     * one of the user's role slugs). Other users' responses are preserved. After writing,
     * recomputes the full evaluation score from all responses and derives aggregate status.
     *
     * Per-item lock (#203): items in pending_review or approved status are locked for
     * the filler and will throw a ValidationException if the user attempts to overwrite them.
     */
    private function saveShared(
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
            // Build a set of item_ids the current user is responsible for in this snapshot.
            $responsibleItemIds = $this->responsibleItemIds($payload, $userRoleSlugs);

            // Enforce per-item lock: if any of the user's responsible items are in a
            // locked item_status, refuse the draft save for those items. (#203)
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

            // Items already filled by ANOTHER user must not be deleted or overwritten.
            $ownedByOthers = $evaluation->responses
                ->whereIn('item_id', $responsibleItemIds)
                ->filter(fn ($r) => $r->filled_by !== null && (int) $r->filled_by !== (int) $userId)
                ->pluck('item_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            $writableItemIds = array_values(array_diff($responsibleItemIds, $ownedByOthers));

            // Delete ONLY the current user's own rows among writable items.
            if (!empty($writableItemIds)) {
                $evaluation->responses()
                    ->where('filled_by', $userId)
                    ->whereIn('item_id', $writableItemIds)
                    ->delete();
            }

            // Re-write the current user's responses with per-item metadata.
            $this->syncSharedResponses($evaluation, $payload, $type, $answers, $writableItemIds, $userId, $userRoleSlugs);

            // Reload ALL responses (other users' + newly written) for scoring.
            $evaluation->load(['responses', 'evidences']);

            // Recompute full score from all responses (scorer is unmodified).
            $result = (new ScoringStrategyFactory())->for($type)->score($evaluation, $payload);
            $result = EvidenceGate::apply($evaluation, $result);

            // Derive aggregate status from item_status set.
            $aggregateStatus = $this->deriveAggregateStatus($evaluation);

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
                'draft_shared',
                "حفظ مسودة مشتركة — تقييم #{$evaluation->id} — المستخدم #{$userId}",
                $evaluation
            );

            return $evaluation->refresh();
        });
    }

    /**
     * Phase E — Write only the current user's responses with per-item metadata.
     * Only writes rows for items in $responsibleItemIds; other items are skipped.
     */
    private function syncSharedResponses(
        Evaluation $evaluation,
        array $payload,
        FormType $type,
        array $answers,
        array $responsibleItemIds,
        int $userId,
        array $userRoleSlugs
    ): void {
        if (empty($responsibleItemIds)) {
            return;
        }

        $responsibleSet = array_flip($responsibleItemIds);
        $items          = $this->snapshotItems($payload);
        $levelIds       = $this->snapshotLevelIds($payload);
        $itemNotes      = is_array($answers['item_notes'] ?? null) ? $answers['item_notes'] : [];

        // Resolve responsible_role per item from the snapshot.
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
                $note    = $this->snapshotNote($itemNotes, $itemId);
                if ($levelId === null && $note === null) {
                    continue;
                }
                $rows[] = [
                    'item_id'          => $itemId,
                    'indicator_id'     => null,
                    'level_id'         => $levelId !== null && in_array($levelId, $levelIds, true) ? $levelId : null,
                    'note'             => $note,
                    // Phase E metadata
                    'filled_by'        => $userId,
                    'responsible_role' => $itemRoleMap[$itemId] ?? null,
                    'item_status'      => 'draft',
                ];
            }
        } else {
            // rating_scale + checklist: one row per indicator
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
                            // Phase E metadata
                            'filled_by'        => $userId,
                            'responsible_role' => $itemRoleMap[$itemId] ?? null,
                            'item_status'      => 'draft',
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
                            // Phase E metadata
                            'filled_by'        => $userId,
                            'responsible_role' => $itemRoleMap[$itemId] ?? null,
                            'item_status'      => 'draft',
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
     * Phase E — Derive the evaluation's aggregate status from all per-item statuses.
     *
     * Rules (in priority order):
     *  - All active items have 'completed' or 'approved' → EvaluationStatus::Completed
     *  - Any item is 'pending_review'                    → EvaluationStatus::PendingApproval
     *  - Otherwise                                       → EvaluationStatus::Draft
     */
    public function deriveAggregateStatus(Evaluation $evaluation): EvaluationStatus
    {
        $evaluation->loadMissing('responses');

        $statuses = $evaluation->responses->pluck('item_status')->unique()->values()->all();

        if (empty($statuses)) {
            return EvaluationStatus::Draft;
        }

        if (in_array('pending_review', $statuses, true)) {
            return EvaluationStatus::PendingApproval;
        }

        $allDone = collect($statuses)->every(fn ($s) => in_array($s, ['completed', 'approved', 'draft'], true));
        $anyCompleted = in_array('completed', $statuses, true) || in_array('approved', $statuses, true);

        // Consider fully done when every response that was filled is completed/approved.
        // (Items not yet filled stay as 'draft'; we only mark Completed when all non-draft
        //  items are done AND there are no still-draft items from OTHER users on the shared eval.)
        $hasAnyDraft = in_array('draft', $statuses, true);

        if ($anyCompleted && !$hasAnyDraft && !in_array('rejected', $statuses, true)) {
            return EvaluationStatus::Completed;
        }

        return EvaluationStatus::Draft;
    }

    /**
     * Phase E — Return item IDs from the snapshot that are assigned to one of the given roles.
     *
     * @param  string[] $userRoleSlugs
     * @return int[]
     */
    private function responsibleItemIds(array $payload, array $userRoleSlugs): array
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
    private function snapshotNote(array $notes, int $itemId): ?string
    {
        $val = $notes[$itemId] ?? null;
        $val = is_string($val) ? trim($val) : null;

        return ($val === null || $val === '') ? null : $val;
    }

    /** Recompute progress counters from the persisted responses. */
    protected function refreshCounters(Evaluation $evaluation): void
    {
        $evaluation->load('responses');
        $itemIds      = $evaluation->responses->whereNull('indicator_id')->pluck('item_id')->filter()->unique();
        $indicatorIds = $evaluation->responses->whereNotNull('indicator_id')->pluck('indicator_id')->unique();

        // Rubric records per item; rating/checklist record per indicator. Count
        // the distinct answered nodes so the progress UI is meaningful either way.
        $itemsAnswered = $itemIds->count() ?: $evaluation->responses
            ->whereNotNull('indicator_id')->pluck('item_id')->filter()->unique()->count();

        $evaluation->items_completed      = $itemsAnswered;
        $evaluation->indicators_completed = $indicatorIds->count();
        $evaluation->evidence_count       = $evaluation->evidences()->count();
    }
}
