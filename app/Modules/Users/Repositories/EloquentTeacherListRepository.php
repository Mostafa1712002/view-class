<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Repositories\Contracts\TeacherRepository;

class EloquentTeacherListRepository extends BaseUserListRepository implements TeacherRepository
{
    protected function roleSlug(): string
    {
        return 'teacher';
    }

    protected function extraSearchColumns(): array
    {
        return ['employee_id', 'specialization'];
    }
}
