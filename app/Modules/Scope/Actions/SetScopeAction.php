<?php

namespace App\Modules\Scope\Actions;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Scope\Repositories\Contracts\ScopeRepository;

final class SetScopeAction
{
    public function __construct(private ScopeRepository $repo) {}

    /**
     * @param array{company_id?:int|null,school_id?:int|null,academic_year_id?:int|null,academic_term_id?:int|null} $input
     * @return array<string,int|null>
     */
    public function execute(User $user, array $input): array
    {
        $companyId = $this->nullableInt($input['company_id'] ?? null);
        $schoolId = $this->nullableInt($input['school_id'] ?? null);
        $yearId = $this->nullableInt($input['academic_year_id'] ?? null);
        $termId = $this->nullableInt($input['academic_term_id'] ?? null);

        if ($companyId !== null && ! $this->repo->companyExistsFor($user, $companyId)) {
            $companyId = null;
        }
        if ($schoolId !== null && ! $this->repo->schoolExistsFor($user, $schoolId)) {
            $schoolId = null;
        }
        if ($yearId !== null && ! $this->repo->yearExistsFor($user, $yearId)) {
            $yearId = null;
        }
        if ($termId !== null && ! $this->repo->termExistsFor($user, $termId)) {
            $termId = null;
        }

        // ── Previous-period gate (students) ──────────────────────────────────
        // A student may only move OFF the current academic year/term when the
        // school setting `allow_previous_periods` is enabled. This is enforced
        // server-side so a hand-crafted POST cannot bypass a disabled UI control.
        if ($user->isStudent() && ! $this->previousPeriodsAllowed($user)) {
            $currentYearId = $this->currentYearId($user);
            $currentTermId = $this->currentTermId($currentYearId);

            // Force back to the current period regardless of what was submitted.
            $yearId = $currentYearId;
            $termId = $currentTermId;
        }

        // Keep term consistent with year: drop a term that doesn't belong to the
        // selected year.
        if ($termId !== null && $yearId !== null
            && ! AcademicTerm::whereKey($termId)->where('academic_year_id', $yearId)->exists()) {
            $termId = null;
        }

        $resolved = [
            'company_id' => $companyId,
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'academic_term_id' => $termId,
        ];

        session()->put('scope', $resolved);

        return $resolved;
    }

    private function previousPeriodsAllowed(User $user): bool
    {
        return (bool) Setting::get('allow_previous_periods', false, $user->school_id);
    }

    private function currentYearId(User $user): ?int
    {
        return AcademicYear::where('school_id', $user->school_id)
            ->where('is_current', true)
            ->value('id');
    }

    private function currentTermId(?int $yearId): ?int
    {
        if (! $yearId) {
            return null;
        }

        return AcademicTerm::where('academic_year_id', $yearId)
            ->where('is_current', true)
            ->value('id');
    }

    private function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '' || $v === 'all') return null;
        return (int) $v;
    }
}
