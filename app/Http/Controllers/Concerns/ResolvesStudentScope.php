<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;

/**
 * Resolves the effective academic year + term for a student page from the
 * shared scope session (set by the navbar's POST /scope), with the school's
 * current period as the always-safe default.
 *
 * Card #170: changing the academic year/term re-scopes the student's data; a
 * student may only view a non-current period when the school setting
 * `allow_previous_periods` is on. This trait is the single place that logic
 * lives so individual controllers don't re-implement the "is_current ?? request"
 * fallback (and can't drift from the server-side gate in SetScopeAction).
 */
trait ResolvesStudentScope
{
    /** Whether the student may view previous (non-current) periods. */
    protected function studentMayViewPreviousPeriods(User $student): bool
    {
        return (bool) Setting::get('allow_previous_periods', false, $student->school_id);
    }

    /**
     * The effective AcademicYear for the logged-in student.
     * Honours session('scope.academic_year_id') only when previous periods are
     * allowed AND the year belongs to the student's school; otherwise current.
     */
    protected function effectiveAcademicYear(User $student): ?AcademicYear
    {
        $current = AcademicYear::where('school_id', $student->school_id)
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::where('school_id', $student->school_id)
                ->orderByDesc('start_date')
                ->first();

        if (! $this->studentMayViewPreviousPeriods($student)) {
            return $current;
        }

        $scopeYearId = session('scope.academic_year_id');
        if (! $scopeYearId) {
            return $current;
        }

        $year = AcademicYear::whereKey($scopeYearId)
            ->where('school_id', $student->school_id)
            ->first();

        return $year ?: $current;
    }

    /**
     * The effective AcademicTerm (may be null when no terms are defined or the
     * student is locked to the current period and no current term exists).
     */
    protected function effectiveAcademicTerm(User $student, ?AcademicYear $year): ?AcademicTerm
    {
        if (! $year) {
            return null;
        }

        $current = AcademicTerm::where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first();

        if (! $this->studentMayViewPreviousPeriods($student)) {
            return $current;
        }

        $scopeTermId = session('scope.academic_term_id');
        if (! $scopeTermId) {
            return $current;
        }

        $term = AcademicTerm::whereKey($scopeTermId)
            ->where('academic_year_id', $year->id)
            ->first();

        return $term ?: $current;
    }
}
