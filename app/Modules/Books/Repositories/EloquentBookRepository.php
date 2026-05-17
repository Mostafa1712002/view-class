<?php

namespace App\Modules\Books\Repositories;

use App\Models\Book;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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
}
