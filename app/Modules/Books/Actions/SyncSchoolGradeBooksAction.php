<?php

namespace App\Modules\Books\Actions;

use App\Modules\Books\Repositories\Contracts\BookRepository;

/**
 * Replace the active school's grade ↔ book links from a checkbox submission.
 * Validates that every class belongs to the school and every book is in the
 * available pool, then persists the whole change inside one transaction so a
 * failure never leaves a partial save. Other schools' links are never touched.
 */
class SyncSchoolGradeBooksAction
{
    public function __construct(private BookRepository $books) {}

    /**
     * @param array<int,int[]> $selection class_id => [book_id, ...]
     */
    public function execute(int $schoolId, array $selection): void
    {
        $validClassIds = $this->books->classIdsForSchool($schoolId);
        $validBookIds = $this->books->availableBooksForSchool($schoolId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $this->books->syncSchoolGradeBooks($schoolId, $selection, $validClassIds, $validBookIds);
    }
}
