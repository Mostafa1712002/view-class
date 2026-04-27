<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Repositories\Contracts\StudentRepository;

class EloquentStudentListRepository extends BaseUserListRepository implements StudentRepository
{
    protected function roleSlug(): string
    {
        return 'student';
    }

    protected function indexWith(): array
    {
        return ['classRoom', 'section'];
    }
}
