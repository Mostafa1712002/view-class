<?php

namespace App\Modules\Scope\Actions;

use App\Models\User;
use App\Modules\Scope\Repositories\Contracts\ScopeRepository;

final class SetScopeAction
{
    public function __construct(private ScopeRepository $repo) {}

    /**
     * @param array{company_id?:int|null,school_id?:int|null,academic_year_id?:int|null} $input
     * @return array<string,int|null>
     */
    public function execute(User $user, array $input): array
    {
        $companyId = $this->nullableInt($input['company_id'] ?? null);
        $schoolId = $this->nullableInt($input['school_id'] ?? null);
        $yearId = $this->nullableInt($input['academic_year_id'] ?? null);

        if ($companyId !== null && ! $this->repo->companyExistsFor($user, $companyId)) {
            $companyId = null;
        }
        if ($schoolId !== null && ! $this->repo->schoolExistsFor($user, $schoolId)) {
            $schoolId = null;
        }
        if ($yearId !== null && ! $this->repo->yearExistsFor($user, $yearId)) {
            $yearId = null;
        }

        $resolved = [
            'company_id' => $companyId,
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
        ];

        session()->put('scope', $resolved);

        return $resolved;
    }

    private function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '' || $v === 'all') return null;
        return (int) $v;
    }
}
