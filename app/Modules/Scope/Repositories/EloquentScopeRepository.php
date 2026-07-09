<?php

namespace App\Modules\Scope\Repositories;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\EducationalCompany;
use App\Models\School;
use App\Models\User;
use App\Modules\Scope\Repositories\Contracts\ScopeRepository;

final class EloquentScopeRepository implements ScopeRepository
{
    public function companiesFor(User $user): array
    {
        // Super admin sees all active companies; other roles see only their school's company.
        if ($user->isSuperAdmin()) {
            return EducationalCompany::query()
                ->where('status', 'active')
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en'])
                ->toArray();
        }

        // Companies of every school the admin is linked to (card #307).
        $companyIds = School::query()
            ->whereIn('id', $user->managedSchoolIds())
            ->pluck('educational_company_id')
            ->filter()
            ->unique()
            ->all();
        if (empty($companyIds)) {
            return [];
        }

        return EducationalCompany::query()
            ->whereIn('id', $companyIds)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en'])
            ->toArray();
    }

    public function schoolsFor(User $user, ?int $companyId): array
    {
        $query = School::query()->orderBy('sort_order')->orderBy('name_ar');

        if ($companyId) {
            $query->where('educational_company_id', $companyId);
        }

        if (! $user->isSuperAdmin()) {
            // Own school plus any schools the admin is linked to (card #307).
            $query->whereIn('id', $user->managedSchoolIds());
        }

        return $query->get(['id', 'name_ar', 'name_en', 'educational_company_id'])->toArray();
    }

    public function yearsFor(User $user, ?int $schoolId): array
    {
        $schoolId = $user->isSuperAdmin() ? $schoolId : $user->school_id;
        if (! $schoolId) {
            return [];
        }

        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'is_current'])
            ->toArray();
    }

    public function termsFor(User $user, ?int $yearId): array
    {
        if (! $yearId) {
            return [];
        }

        // Term ownership rides on the year's school ownership: only return terms
        // for a year the user is allowed to see.
        $yearQuery = AcademicYear::whereKey($yearId);
        if (! $user->isSuperAdmin()) {
            $yearQuery->where('school_id', $user->school_id);
        }
        if (! $yearQuery->exists()) {
            return [];
        }

        return AcademicTerm::query()
            ->where('academic_year_id', $yearId)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'is_current', 'academic_year_id'])
            ->toArray();
    }

    public function companyExistsFor(User $user, int $companyId): bool
    {
        if ($user->isSuperAdmin()) {
            return EducationalCompany::whereKey($companyId)->exists();
        }

        return optional($user->school)->educational_company_id === $companyId;
    }

    public function schoolExistsFor(User $user, int $schoolId): bool
    {
        if ($user->isSuperAdmin()) {
            return School::whereKey($schoolId)->exists();
        }

        return in_array((int) $schoolId, $user->managedSchoolIds(), true);
    }

    public function yearExistsFor(User $user, int $yearId): bool
    {
        $query = AcademicYear::whereKey($yearId);
        if (! $user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }

        return $query->exists();
    }

    public function termExistsFor(User $user, int $termId): bool
    {
        $query = AcademicTerm::whereKey($termId);
        if (! $user->isSuperAdmin()) {
            $query->whereHas('academicYear', fn ($q) => $q->where('school_id', $user->school_id));
        }

        return $query->exists();
    }
}
