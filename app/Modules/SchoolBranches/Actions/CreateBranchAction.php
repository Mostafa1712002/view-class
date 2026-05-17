<?php

namespace App\Modules\SchoolBranches\Actions;

use App\Modules\SchoolBranches\Models\SchoolBranch;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;

final class CreateBranchAction
{
    public function __construct(private SchoolBranchRepository $branches) {}

    public function execute(array $data): SchoolBranch
    {
        return $this->branches->create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? null,
        ]);
    }
}
