<?php

namespace App\Modules\Promotion\Services;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Modules\Promotion\Repositories\Contracts\PromotionRepository;
use App\Modules\Promotion\Support\StandardGrades;

/**
 * Pure planner for year-end promotion. Reads the current DB state and produces
 * an explicit list of moves (promoted / graduated / overflow_moved / not_moved)
 * WITHOUT writing anything — the same plan powers the preview and the execute.
 *
 * Rules (from the card):
 *  - grade ordinal N → N+1; the top grade (no N+1) graduates.
 *  - class number k → class number k in the next grade, same gender track.
 *  - only students whose enrollment result is 'passed' move.
 *  - capacity overflow: the alphabetically-LAST students spill to class k+1;
 *    if there is no class to receive them they stay put (not_moved) and the
 *    principal is alerted.
 */
class PromotionPlanner
{
    public function __construct(private PromotionRepository $repo) {}

    /**
     * @return array{
     *   moves: array<int,array<string,mixed>>,
     *   summary: array<string,int>,
     *   blockers: array<int,string>,
     *   warnings: array<int,string>,
     *   capacity_alerts: array<int,array{grade:string,number:int,count:int}>
     * }
     */
    public function plan(School $school, AcademicYear $src, AcademicYear $dst): array
    {
        $grades = $this->repo->gradesIndex($school);

        $this->moves = [];
        $this->blockers = [];
        $this->warnings = [];
        $this->capacityAlerts = [];
        $this->reserved = [];

        // High → low so a grade empties before the grade below it arrives.
        for ($n = 12; $n >= 1; $n--) {
            /** @var Section|null $srcGrade */
            $srcGrade = $grades->get($n);
            if (! $srcGrade) {
                continue;
            }

            $dstGrade = $grades->get($n + 1); // null → this grade graduates
            $srcClasses = $this->repo->classesOfGradeForYear($srcGrade->id, $src->id);

            foreach ($srcClasses as $srcClass) {
                $passed = $this->repo->passedStudents($srcClass);
                if ($passed->isEmpty()) {
                    continue;
                }

                if ($dstGrade === null) {
                    foreach ($passed as $student) {
                        $this->record($student, 'graduated', $srcGrade, $srcClass, $srcGrade, null);
                    }

                    continue;
                }

                if ($srcClass->number === null) {
                    $this->blockers[] = __('schools.promotion_blocker_class_number', [
                        'grade' => $srcGrade->name,
                        'class' => $srcClass->name,
                    ]);

                    continue;
                }

                $this->place($passed->all(), $srcGrade, $srcClass, $dstGrade, $dst->id, (int) $srcClass->number);
            }
        }

        return [
            'moves' => $this->moves,
            'summary' => $this->summary(),
            'blockers' => array_values(array_unique($this->blockers)),
            'warnings' => array_values(array_unique($this->warnings)),
            'capacity_alerts' => $this->capacityAlerts,
        ];
    }

    /** @var array<int,array<string,mixed>> */
    private array $moves = [];
    private array $blockers = [];
    private array $warnings = [];
    private array $capacityAlerts = [];
    /** dstClassId => seats already reserved earlier in this same plan. */
    private array $reserved = [];

    /**
     * Place a class's passed students into the destination grade starting at
     * class number $targetNumber, cascading overflow to the next number.
     *
     * @param  array<int,\App\Models\User>  $students  sorted by name ASC
     */
    private function place(array $students, Section $srcGrade, ClassRoom $srcClass, Section $dstGrade, int $dstYearId, int $targetNumber): void
    {
        $remaining = $students;
        $k = $targetNumber;

        while (! empty($remaining)) {
            $dstClass = $this->repo->classInGrade($dstGrade->id, $dstYearId, $k, (string) $srcClass->gender);

            if ($dstClass === null) {
                foreach ($remaining as $student) {
                    $this->record($student, 'not_moved', $srcGrade, $srcClass, null, null, 'no_class_'.$k);
                }
                $this->capacityAlerts[] = [
                    'grade' => $dstGrade->name,
                    'number' => $k,
                    'count' => count($remaining),
                ];
                $this->warnings[] = __('schools.promotion_warn_no_class', [
                    'grade' => $dstGrade->name,
                    'number' => $k,
                    'count' => count($remaining),
                ]);

                return;
            }

            $reserved = $this->reserved[$dstClass->id] ?? 0;
            $free = max(0, (int) $dstClass->capacity - $this->repo->occupiedSeats($dstClass) - $reserved);
            // action for THIS class: natural target = promoted, cascaded = overflow.
            $action = ($k === $targetNumber) ? 'promoted' : 'overflow_moved';

            if (count($remaining) <= $free) {
                foreach ($remaining as $student) {
                    $this->record($student, $action, $srcGrade, $srcClass, $dstGrade, $dstClass);
                }
                $this->reserved[$dstClass->id] = $reserved + count($remaining);

                return;
            }

            $keep = array_slice($remaining, 0, $free);      // alphabetically first stay
            $overflow = array_slice($remaining, $free);      // alphabetically last cascade
            foreach ($keep as $student) {
                $this->record($student, $action, $srcGrade, $srcClass, $dstGrade, $dstClass);
            }
            $this->reserved[$dstClass->id] = $reserved + count($keep);

            $remaining = $overflow;
            $k++;
        }
    }

    private function record(
        $student,
        string $action,
        Section $fromSection,
        ?ClassRoom $fromClass,
        ?Section $toSection,
        ?ClassRoom $toClass,
        ?string $reason = null,
    ): void {
        $this->moves[] = [
            'student_id' => $student->id,
            'student_name' => $student->name_ar ?: $student->name,
            'action' => $action,
            'from_section_id' => $fromSection?->id,
            'from_section_name' => $fromSection?->name,
            'from_class_id' => $fromClass?->id,
            'from_class_name' => $fromClass?->name,
            'to_section_id' => $toSection?->id,
            'to_section_name' => $toSection?->name,
            'to_class_id' => $toClass?->id,
            'to_class_name' => $toClass?->name,
            'reason' => $reason,
        ];
    }

    /** @return array<string,int> */
    private function summary(): array
    {
        $s = ['promoted' => 0, 'graduated' => 0, 'overflow_moved' => 0, 'not_moved' => 0];
        foreach ($this->moves as $m) {
            $s[$m['action']]++;
        }

        return $s;
    }
}
