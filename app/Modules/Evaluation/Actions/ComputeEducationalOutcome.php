<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationOutcome;
use App\Models\User;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EducationalOutcomeCalculator;
use App\Modules\Evaluation\Services\EducationalOutcomeResolver;
use Illuminate\Support\Facades\DB;

/**
 * Phase C (#205) — Create a new EvaluationOutcome record with computed stats.
 *
 * Method resolution order:
 *   1. $data['method']  — explicit override from the caller (optional)
 *   2. School-level setting via EducationalOutcomeResolver
 *   3. Global setting   via EducationalOutcomeResolver
 *   4. Hard-coded fallback 'all_registered' (non-breaking default)
 *
 * Computed fields (final_average, method_used, *_count, scores_sum) are written
 * via explicit property assignment — they are NOT in $fillable, so a crafted
 * request payload cannot bypass the computation pipeline.
 */
class ComputeEducationalOutcome
{
    public function __construct(
        private readonly EducationalOutcomeCalculator $calculator,
        private readonly EducationalOutcomeResolver   $resolver,
        private readonly AuditTrail                   $audit,
    ) {
    }

    /**
     * @param  array{
     *     school_id: int,
     *     test_name: string,
     *     students: array,
     *     method?: string,
     *     educational_company_id?: int|null,
     *     teacher_id?: int|null,
     *     subject_id?: int|null,
     *     grade_level?: string|null,
     *     class_label?: string|null,
     *     test_type?: string|null,
     *     source?: string,
     *     test_date?: string|null,
     * } $data
     */
    public function execute(array $data, User $actor): EvaluationOutcome
    {
        return DB::transaction(function () use ($data, $actor) {
            // 1. Resolve method
            $method = !empty($data['method'])
                ? $data['method']
                : $this->resolver->methodFor((int) $data['school_id']);

            // 2. Compute stats
            $stats = $this->calculator->compute($data['students'], $method);

            // 3. Build record — mass-assign only $fillable fields via the constructor
            //    (do NOT create() yet: method_used etc. are guarded/NOT NULL and are
            //    set below, then persisted in one save so the INSERT is complete).
            $outcome = new EvaluationOutcome([
                'school_id'               => $data['school_id'],
                'educational_company_id'  => $data['educational_company_id'] ?? null,
                'teacher_id'              => $data['teacher_id'] ?? null,
                'subject_id'              => $data['subject_id'] ?? null,
                'grade_level'             => $data['grade_level'] ?? null,
                'class_label'             => $data['class_label'] ?? null,
                'test_name'               => $data['test_name'],
                'test_type'               => $data['test_type'] ?? null,
                'source'                  => $data['source'] ?? 'manual',
                'students'                => $data['students'],
                'test_date'               => $data['test_date'] ?? null,
                'imported_at'             => null,
                'computed_by'             => $actor->id,
            ]);

            // 4. Set computed fields via explicit assignment (not mass-assign)
            $outcome->registered_count = $stats['registered'];
            $outcome->present_count    = $stats['present'];
            $outcome->absent_count     = $stats['absent'];
            $outcome->scores_sum       = $stats['sum'];
            $outcome->method_used      = $method;
            $outcome->final_average    = $stats['average'];
            $outcome->approval_status  = 'draft';
            $outcome->save();

            // 5. Audit
            $this->audit->created(
                $outcome,
                "إنشاء ناتج تعليمي #{$outcome->id} — الاختبار: {$outcome->test_name} — الطريقة: {$method} — المتوسط: {$stats['average']}"
            );

            return $outcome->refresh();
        });
    }
}
