<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Repositories\Contracts\ParentRepository;

class EloquentParentListRepository extends BaseUserListRepository implements ParentRepository
{
    protected function roleSlug(): string
    {
        return 'parent';
    }

    protected function indexWith(): array
    {
        return ['children'];
    }

    protected function extraSearchColumns(): array
    {
        return ['phone'];
    }
}
