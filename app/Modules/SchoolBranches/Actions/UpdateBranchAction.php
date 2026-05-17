<?php

namespace App\Modules\SchoolBranches\Actions;

use App\Modules\SchoolBranches\Models\SchoolBranch;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;

final class UpdateBranchAction
{
    public function __construct(private SchoolBranchRepository $branches) {}

    public function execute(SchoolBranch $branch, array $data): SchoolBranch
    {
        $payload = array_filter([
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn ($v) => $v !== null);

        // is_active is a checkbox — must be explicit so unchecked → false reaches the DB.
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        return $this->branches->update($branch, $payload);
    }
}
