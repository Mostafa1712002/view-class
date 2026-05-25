<?php

namespace App\Modules\Books\Repositories;

use App\Models\Book;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EloquentBookRepository implements BookRepository
{
    public function paginate(?int $schoolId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Book::query()->with(['subject', 'academicTerm']);

        if ($schoolId !== null) {
            // School-scoped: own books OR ministry books visible to all
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhere('is_ministry', true);
            });
        }

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['grade_level'])) {
            $query->where('grade_level', $filters['grade_level']);
        }

        if (!empty($filters['academic_term_id'])) {
            $query->where('academic_term_id', $filters['academic_term_id']);
        }

        if (isset($filters['is_ministry']) && $filters['is_ministry'] !== '' && $filters['is_ministry'] !== null) {
            $query->where('is_ministry', (bool) $filters['is_ministry']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes): Book
    {
        return Book::create($attributes);
    }

    public function update(Book $book, array $attributes): Book
    {
        $book->fill($attributes);
        $book->save();
        return $book;
    }

    public function delete(Book $book): void
    {
        if ($book->file_path) {
            Storage::disk('public')->delete($book->file_path);
        }
        if ($book->cover_path) {
            Storage::disk('public')->delete($book->cover_path);
        }
        $book->delete();
    }

    public function findScoped(int $id, ?int $schoolId): ?Book
    {
        $query = Book::query();
        if ($schoolId !== null) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhere('is_ministry', true);
            });
        }
        return $query->find($id);
    }

    public function forStudent(int $schoolId, int $gradeLevel): Collection
    {
        return Book::query()
            ->with('subject')
            ->where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhere('is_ministry', true);
            })
            ->where(function ($q) use ($gradeLevel) {
                $q->whereNull('grade_level')->orWhere('grade_level', $gradeLevel);
            })
            ->orderByDesc('id')
            ->get();
    }

    public function availableBooksForSchool(?int $schoolId): Collection
    {
        return Book::query()
            ->with('subject')
            ->where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->where('is_ministry', true)->orWhereNull('school_id');
                if ($schoolId !== null) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderByDesc('is_ministry')
            ->orderBy('title')
            ->get();
    }

    public function linkedBookIdsByClass(int $schoolId): array
    {
        $rows = DB::table('school_grade_books')
            ->where('school_id', $schoolId)
            ->get(['class_id', 'book_id']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->class_id][] = (int) $row->book_id;
        }

        return $map;
    }

    public function classIdsForSchool(int $schoolId): array
    {
        $sectionIds = Section::query()->where('school_id', $schoolId)->pluck('id');

        return ClassRoom::query()
            ->whereIn('section_id', $sectionIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function syncSchoolGradeBooks(int $schoolId, array $selection, array $validClassIds, array $validBookIds): void
    {
        $validClassIds = array_map('intval', $validClassIds);
        $validBookIds = array_map('intval', $validBookIds);

        // Build the deduped, scope-validated rows to insert.
        $rows = [];
        $now = now();
        foreach ($selection as $classId => $bookIds) {
            $classId = (int) $classId;
            if (!in_array($classId, $validClassIds, true)) {
                continue; // ignore classes outside this school
            }
            foreach (array_unique(array_map('intval', (array) $bookIds)) as $bookId) {
                if (!in_array($bookId, $validBookIds, true)) {
                    continue; // ignore books outside the available pool
                }
                $rows[] = [
                    'school_id' => $schoolId,
                    'class_id' => $classId,
                    'book_id' => $bookId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($schoolId, $validClassIds, $rows) {
            // Only clear links for grades that belong to this school.
            DB::table('school_grade_books')
                ->where('school_id', $schoolId)
                ->whereIn('class_id', $validClassIds ?: [0])
                ->delete();

            if ($rows !== []) {
                DB::table('school_grade_books')->insert($rows);
            }
        });
    }
}
