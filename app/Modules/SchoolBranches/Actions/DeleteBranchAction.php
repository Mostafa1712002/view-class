<?php

namespace App\Modules\SchoolBranches\Actions;

use App\Modules\SchoolBranches\Models\SchoolBranch;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;
use RuntimeException;

final class DeleteBranchAction
{
    public function __construct(private SchoolBranchRepository $branches) {}

    public function execute(SchoolBranch $branch): bool
    {
        if ($this->branches->countSchools($branch) > 0) {
            throw new RuntimeException('branch_has_schools');
        }
        return $this->branches->delete($branch);
    }
}
