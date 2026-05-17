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

    public function replaceColumns(GradeReport $report, array $columns): GradeReport
    {
        return DB::transaction(function () use ($report, $columns) {
            $keptIds = [];
            $order = 0;
            foreach ($columns as $row) {
                $order++;
                $row = array_filter($row, fn($v) => $v !== null && $v !== '');
                $title = trim((string)($row['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $payload = [
                    'subject_id' => $row['subject_id'] ?? null,
                    'title' => $title,
                    'type' => $row['type'] ?? 'numeric',
                    'weight' => (float) ($row['weight'] ?? 0),
                    'max_score' => (float) ($row['max_score'] ?? 100),
                    'pass_threshold' => isset($row['pass_threshold']) ? (float) $row['pass_threshold'] : null,
                    'sort_order' => $order,
                    'is_in_total' => (bool) ($row['is_in_total'] ?? true),
                    'is_visible' => (bool) ($row['is_visible'] ?? true),
                ];
                if (!empty($row['id'])) {
                    $col = $report->columns()->whereKey($row['id'])->first();
                    if ($col) {
                        $col->update($payload);
                        $keptIds[] = $col->id;
                        continue;
                    }
                }
                $new = $report->columns()->create($payload);
                $keptIds[] = $new->id;
            }
            // Cascade delete student values for removed columns then delete columns
            $toDelete = $report->columns()->whereNotIn('id', $keptIds ?: [0])->pluck('id');
            if ($toDelete->isNotEmpty()) {
                DB::table('student_grade_values')->whereIn('grade_report_column_id', $toDelete)->delete();
                $report->columns()->whereIn('id', $toDelete)->delete();
            }
            return $report->fresh(['columns']);
        });
    }

    public function delete(GradeReport $report): void
    {
        $report->delete();
    }
}
