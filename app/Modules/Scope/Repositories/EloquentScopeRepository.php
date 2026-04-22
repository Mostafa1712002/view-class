<?php

namespace App\Modules\Scope\Repositories;

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
                ->orderBy('sort_order')
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en'])
                ->toArray();
        }

        $schoolCompanyId = optional($user->school)->educational_company_id;
        if (! $schoolCompanyId) {
            return [];
        }

        return EducationalCompany::query()
            ->whereKey($schoolCompanyId)
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
            $query->where('id', $user->school_id);
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

        return $user->school_id === $schoolId;
    }

    public function yearExistsFor(User $user, int $yearId): bool
    {
        $query = AcademicYear::whereKey($yearId);
        if (! $user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }

        return $query->exists();
    }
}
