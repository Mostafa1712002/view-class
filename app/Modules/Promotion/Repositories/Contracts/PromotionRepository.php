<?php

namespace App\Modules\Promotion\Repositories\Contracts;

use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Support\Collection;

/**
 * Data access for the automatic year-end promotion engine. Every method is
 * bounded to one school (multi-tenant scope enforced here, per CLAUDE.md), so
 * a planner or action can never read/write another tenant's rows.
 */
interface PromotionRepository
{
    /**
     * Active grades of the school that carry a standard ordinal, keyed by
     * grade_number (1..12). Grades without an ordinal are excluded — the caller
     * treats their presence (with classes in the source year) as a blocker.
     *
     * @return Collection<int,\App\Models\Section>
     */
    public function gradesIndex(School $school): Collection;

    /**
     * Classes of one grade (section) for a given academic year, ordered by
     * their `number`.
     *
     * @return Collection<int,ClassRoom>
     */
    public function classesOfGradeForYear(int $sectionId, int $yearId): Collection;

    /**
     * The class of a grade for a year with the given number AND gender track.
     * Null when no such class exists (destination not prepared / gender mismatch).
     */
    public function classInGrade(int $sectionId, int $yearId, int $number, string $gender): ?ClassRoom;

    /**
     * Students of a class whose enrollment result is 'passed', ordered by name
     * (deterministic — Arabic name, falling back to name). Only these promote.
     *
     * @return Collection<int,\App\Models\User>
     */
    public function passedStudents(ClassRoom $class): Collection;

    /**
     * How many seats a destination class already holds (any current enrollment
     * occupies a seat). Used to compute remaining capacity before placing.
     */
    public function occupiedSeats(ClassRoom $class): int;

    // --- Mutations (called inside a DB transaction by the actions) ---

    /** Remove one enrollment pivot row. */
    public function detachStudent(int $classId, int $studentId): void;

    /** Add/refresh one enrollment pivot row with the given result. */
    public function attachStudent(int $classId, int $studentId, string $result = 'pending'): void;

    /** Point the student record at a grade + class (denormalised placement). */
    public function setPlacement(int $studentId, ?int $sectionId, ?int $classId): void;

    /** Set/clear the graduation flag on a student. */
    public function setGraduated(int $studentId, bool $graduated): void;
}
