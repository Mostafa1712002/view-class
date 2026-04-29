<?php

namespace App\Modules\GradeReports\Repositories;

use App\Models\GradeReport;
use App\Modules\GradeReports\Repositories\Contracts\GradeReportRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentGradeReportRepository implements GradeReportRepository
{
    public function paginate(?int $schoolId, ?string $type = null, int $perPage = 20): LengthAwarePaginator
    {
        $q = GradeReport::query()
            ->with(['classRoom', 'academicTerm', 'creator'])
            ->withCount('columns');

        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }
        if ($type) {
            $q->where('type', $type);
        }
        return $q->latest()->paginate($perPage);
    }

    public function findScoped(int $id, ?int $schoolId): ?GradeReport
    {
        $q = GradeReport::with(['columns', 'ratings', 'classRoom', 'academicTerm']);
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }
        return $q->find($id);
    }

    public function createDynamic(array $payload, ?int $schoolId, int $createdBy): GradeReport
    {
        return DB::transaction(function () use ($payload, $schoolId, $createdBy) {
            $report = GradeReport::create([
                'school_id' => $schoolId ?? ($payload['school_id'] ?? null),
                'academic_year_id' => $payload['academic_year_id'] ?? null,
                'academic_term_id' => $payload['academic_term_id'] ?? null,
                'class_id' => $payload['class_id'] ?? null,
                'type' => 'dynamic',
                'title' => $payload['title'],
                'grade_input_starts_at' => $payload['grade_input_starts_at'] ?? null,
                'grade_input_ends_at' => $payload['grade_input_ends_at'] ?? null,
                'calc_starts_at' => $payload['calc_starts_at'] ?? null,
                'calc_ends_at' => $payload['calc_ends_at'] ?? null,
                'opens_at' => $payload['opens_at'] ?? null,
                'closes_at' => $payload['closes_at'] ?? null,
                'include_behavior' => (bool) ($payload['include_behavior'] ?? false),
                'show_subject_bilingual' => (bool) ($payload['show_subject_bilingual'] ?? false),
                'visible_to_student' => (bool) ($payload['visible_to_student'] ?? true),
                'visible_to_parent' => (bool) ($payload['visible_to_parent'] ?? true),
                'visible_to_teacher' => (bool) ($payload['visible_to_teacher'] ?? true),
                'header_settings' => $payload['header_settings'] ?? null,
                'footer_settings' => $payload['footer_settings'] ?? null,
                'created_by' => $createdBy,
            ]);

            // Seed default columns: 4 numeric placeholders so the report is non-empty
            $defaults = [
                ['title' => 'الواجبات', 'weight' => 10, 'max_score' => 10, 'sort_order' => 1],
                ['title' => 'الاختبارات القصيرة', 'weight' => 20, 'max_score' => 20, 'sort_order' => 2],
                ['title' => 'منتصف الفصل', 'weight' => 30, 'max_score' => 30, 'sort_order' => 3],
                ['title' => 'النهائي', 'weight' => 40, 'max_score' => 40, 'sort_order' => 4],
            ];
            foreach ($defaults as $c) {
                $report->columns()->create(array_merge($c, [
                    'type' => 'numeric',
                    'is_in_total' => true,
                    'is_visible' => true,
                ]));
            }

            return $report->load('columns');
        });
    }

    public function update(GradeReport $report, array $payload): GradeReport
    {
        $report->update($payload);
        return $report->fresh(['columns', 'ratings']);
    }

    public function delete(GradeReport $report): void
    {
        $report->delete();
    }
}
