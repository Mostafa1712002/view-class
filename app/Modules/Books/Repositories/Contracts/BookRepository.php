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

    /** Available book pool for a school: ministry books ∪ the school's own active books. */
    public function availableBooksForSchool(?int $schoolId): Collection;

    /**
     * Map of class_id => [book_id, ...] already linked for this school.
     * @return array<int,int[]>
     */
    public function linkedBookIdsByClass(int $schoolId): array;

    /** @return int[] valid class ids belonging to the school's sections. */
    public function classIdsForSchool(int $schoolId): array;

    /**
     * Transactionally replace the school's grade↔book links.
     * @param array<int,int[]> $selection  class_id => [book_id, ...]
     * @param int[] $validClassIds  classes that belong to this school (scope guard)
     * @param int[] $validBookIds   books in the available pool (scope guard)
     */
    public function syncSchoolGradeBooks(int $schoolId, array $selection, array $validClassIds, array $validBookIds): void;
}
