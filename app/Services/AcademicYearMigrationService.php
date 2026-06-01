<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Year-rollover migrations for a single school. All operations are additive and
 * idempotent (dedupe): re-running never duplicates rows and never deletes the
 * source data — the previous year's records remain as history.
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
     * destination class is skipped.
     *
     * @return array{migrated:int, skipped:int}
     */
    public function promoteStudents(ClassRoom $source, ClassRoom $destination): array
    {
        $sourceStudentIds = $source->students()->pluck('users.id')->all();
        $destStudentIds = array_flip($destination->students()->pluck('users.id')->all());

        $toAttach = [];
        $skipped = 0;
        foreach ($sourceStudentIds as $sid) {
            if (isset($destStudentIds[$sid])) {
                $skipped++;
                continue;
            }
            $toAttach[] = $sid;
        }

        if (!empty($toAttach)) {
            $destination->students()->syncWithoutDetaching($toAttach);
        }

        return ['migrated' => count($toAttach), 'skipped' => $skipped];
    }
}
