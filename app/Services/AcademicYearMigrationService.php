<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Additive year-rollover migrations for a single school. Source records remain
 * intact; student promotions are audited and guarded against repeated moves.
 */
class AcademicYearMigrationService
{
    /**
     * Copy every class of the source academic year into the destination year.
     * A class already present in the destination year (same section + name) is
     * skipped.
     *
     * @return array{created:int, skipped:int}
     */
    public function migrateClasses(School $school, AcademicYear $source, AcademicYear $destination): array
    {
        $sourceClasses = ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $school->id))
            ->where('academic_year_id', $source->id)
            ->get();

        $existing = array_flip(
            ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $school->id))
                ->where('academic_year_id', $destination->id)
                ->get()
                ->map(fn ($c) => $c->section_id.'|'.$c->name)
                ->all()
        );

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($sourceClasses, &$existing, $destination, &$created, &$skipped) {
            foreach ($sourceClasses as $class) {
                $key = $class->section_id.'|'.$class->name;
                if (isset($existing[$key])) {
                    $skipped++;
                    continue;
                }

                ClassRoom::create([
                    'section_id' => $class->section_id,
                    'academic_year_id' => $destination->id,
                    'name' => $class->name,
                    'grade_level' => $class->grade_level,
                    'gender' => $class->gender,
                    'number' => $class->number,
                    'division' => $class->division,
                    'lead_teacher_id' => $class->lead_teacher_id,
                    'capacity' => $class->capacity,
                    'room' => $class->room,
                    'is_active' => $class->is_active,
                ]);

                $existing[$key] = true;
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Promote students from a source class into a destination class. The source
     * enrolment is left intact (history); a student already enrolled in the
     * destination class is skipped. Prior executed moves into the destination
     * year are rejected, unless their batch has been rolled back.
     *
     * @return array{migrated:int, skipped:int}
     */
    public function promoteStudents(ClassRoom $source, ClassRoom $destination): array
    {
        return DB::transaction(function () use ($source, $destination) {
            if (! $source->academic_year_id || ! $destination->academic_year_id) {
                throw ValidationException::withMessages([
                    'destination_class_id' => __('schools.promotion_year_required'),
                ]);
            }

            $studentIds = $source->students()->pluck('users.id')->all();
            User::whereIn('id', $studentIds)->orderBy('id')->lockForUpdate()->get();
            $count = PromotionBatchItem::movedIntoYear($destination->academic_year_id)
                ->whereIn('student_id', $studentIds)->distinct()->count('student_id');
            if ($count) {
                throw ValidationException::withMessages([
                    'destination_class_id' => __('schools.promotion_students_already_promoted', ['count' => $count]),
                ]);
            }

            $existing = $destination->students()->pluck('users.id')->all();
            $toAttach = array_values(array_diff($studentIds, $existing));
            $skipped = count($studentIds) - count($toAttach);
            $batch = PromotionBatch::create([
                'school_id' => $source->section->school_id,
                'source_year_id' => $source->academic_year_id,
                'destination_year_id' => $destination->academic_year_id,
                'status' => 'executed',
                'executed_by' => auth()->id(),
                'executed_at' => now(),
                'summary' => ['promoted' => count($toAttach), 'not_moved' => $skipped,
                    'graduated' => 0, 'overflow_moved' => 0, 'source_enrolments_retained' => true],
            ]);
            $destination->students()->syncWithoutDetaching($toAttach);
            foreach ($studentIds as $studentId) {
                $moved = in_array($studentId, $toAttach);
                $batch->items()->create([
                    'student_id' => $studentId,
                    'action' => $moved ? 'promoted' : 'not_moved',
                    'from_section_id' => $source->section_id,
                    'from_class_id' => $source->id,
                    'to_section_id' => $moved ? $destination->section_id : $source->section_id,
                    'to_class_id' => $moved ? $destination->id : $source->id,
                    'reason' => $moved ? null : 'already enrolled',
                ]);
            }

            return ['migrated' => count($toAttach), 'skipped' => $skipped];
        });
    }
}
