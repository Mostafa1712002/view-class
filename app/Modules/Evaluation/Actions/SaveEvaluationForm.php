<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * Task 2 + Task 3 — create or update an evaluation form, syncing its performance
 * levels according to the form type (rubric/rating use levels; checklist uses none).
 */
class SaveEvaluationForm
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    public function execute(?EvaluationForm $form, array $data, ?int $schoolId, int $userId): EvaluationForm
    {
        return DB::transaction(function () use ($form, $data, $schoolId, $userId) {
            $isNew    = $form === null;
            $settings = (array) ($data['settings'] ?? []);
            $type     = $data['type'];

            $payload = [
                'title'                    => $data['title'],
                'description'              => $data['description'] ?? null,
                'internal_notes'           => $data['internal_notes'] ?? null,
                'type'                     => $type,
                'usage_domain'             => $data['usage_domain'],
                'start_date'               => $data['start_date'] ?? null,
                'close_date'               => $data['close_date'] ?? null,
                'levels_count'             => $this->levelCountFor($type, $data),
                'is_class_visit_only'      => (bool) ($settings['class_visit_only'] ?? false),
                'links_to_job_performance' => (bool) ($settings['links_to_job_performance'] ?? false),
                'settings'                 => $settings,
            ];

            if ($isNew) {
                $payload['school_id']  = $schoolId;
                $payload['created_by'] = $userId;
                $payload['status']     = FormStatus::Draft->value;
                $form = EvaluationForm::create($payload);
                $this->audit->created($form, "إنشاء نموذج تقييم: {$form->title}");
            } else {
                $old = $form->toArray();
                $form->fill($payload)->save();
                $this->audit->updated($form, "تعديل نموذج تقييم: {$form->title}", $old);
            }

            $this->syncLevels($form, $type, (array) ($data['level_labels'] ?? []));

            return $form->refresh();
        });
    }

    private function levelCountFor(string $type, array $data): int
    {
        if ($type === FormType::Checklist->value) {
            return 0;
        }

        return (int) ($data['levels_count'] ?? 0);
    }

    /** Rebuild the form's levels. Checklist has none; rubric/rating store ordered labels. */
    private function syncLevels(EvaluationForm $form, string $type, array $labels): void
    {
        $form->levels()->delete();

        if ($type === FormType::Checklist->value) {
            return;
        }

        $labels = array_values(array_filter($labels, fn ($l) => trim((string) $l) !== ''));
        $count  = count($labels);
        if ($count === 0) {
            return;
        }

        foreach ($labels as $i => $label) {
            $rank = $i + 1;
            $form->levels()->create([
                'label'      => $label,
                'value'      => $rank,
                // Rubric: level N of M is worth (N/M)*100% of the item weight.
                'percentage' => round(($rank / $count) * 100, 2),
                'sort_order' => $rank,
            ]);
        }
    }
}
