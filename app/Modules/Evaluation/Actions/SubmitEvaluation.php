<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
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
     * @param array $answers see WritesResponses::syncResponses()
     *
     * @throws ValidationException when locked or completeness/evidence gates fail.
     */
    public function submit(Evaluation $evaluation, array $answers, ?string $generalNotes): Evaluation
    {
        if ($evaluation->status instanceof EvaluationStatus && $evaluation->status->isLocked()) {
            throw ValidationException::withMessages([
                'evaluation' => __('evaluation.execute.errors.locked'),
            ]);
        }

        $form     = $evaluation->form;
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
