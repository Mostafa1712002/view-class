<?php

namespace App\Modules\Books\Repositories\Contracts;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookRepository
{
    /** @param array<string,mixed> $filters */
    public function paginate(?int $schoolId, array $filters, int $perPage = 15): LengthAwarePaginator;

    /** @param array<string,mixed> $attributes */
    public function create(array $attributes): Book;

    /** @param array<string,mixed> $attributes */
    public function update(Book $book, array $attributes): Book;

    public function delete(Book $book): void;

    public function findScoped(int $id, ?int $schoolId): ?Book;

    /** Books visible to a student. */
    public function forStudent(int $schoolId, int $gradeLevel): Collection;
}
