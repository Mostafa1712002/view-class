<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Modules\Evaluation\Actions\Concerns\WritesResponses;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 11 — save an evaluation as a draft. No completeness checks; the evaluator
 * may save partial answers at any time. Locked evaluations cannot be drafted.
 */
class SaveEvaluationDraft
{
    use WritesResponses;

    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * @param array $answers see WritesResponses::syncResponses()
     *
     * @throws ValidationException when the evaluation is locked.
     */
    public function save(Evaluation $evaluation, array $answers, ?string $generalNotes): Evaluation
    {
        if ($evaluation->status instanceof EvaluationStatus && $evaluation->status->isLocked()) {
            throw ValidationException::withMessages([
                'evaluation' => __('evaluation.execute.errors.locked'),
            ]);
        }

        $snapshot = $evaluation->snapshot;
        $type     = $evaluation->form->type ?? FormType::tryFrom((string) ($snapshot->payload['form']['type'] ?? ''));

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
