<?php

namespace App\Modules\Books\Actions;

use App\Models\Book;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadBookAction
{
    public function __construct(private BookRepository $books) {}

    /**
     * @param array<string,mixed> $data Validated form data.
     * @param int|null $schoolId Active scope school id (null = ministry/global).
     */
    public function execute(array $data, ?int $schoolId, int $userId, ?UploadedFile $file = null, ?UploadedFile $cover = null): Book
    {
        $payload = [
            'school_id' => (bool) ($data['is_ministry'] ?? false) ? null : $schoolId,
            'subject_id' => $data['subject_id'],
            'grade_level' => $data['grade_level'] ?? null,
            'academic_term_id' => $data['academic_term_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => $data['source'],
            'is_ministry' => (bool) ($data['is_ministry'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by' => $userId,
        ];

        if ($data['source'] === Book::SOURCE_FILE && $file) {
            $payload['file_path'] = $file->store('books', 'public');
        } elseif ($data['source'] === Book::SOURCE_EXTERNAL) {
            $payload['external_url'] = $data['external_url'] ?? null;
        }

        if ($cover) {
            $payload['cover_path'] = $cover->store('books/covers', 'public');
        }

        return $this->books->create($payload);
    }

    /**
     * Update an existing book; replaces file/cover only when new uploads are provided.
     */
    public function update(Book $book, array $data, ?UploadedFile $file = null, ?UploadedFile $cover = null): Book
    {
        $payload = [
            'subject_id' => $data['subject_id'],
            'grade_level' => $data['grade_level'] ?? null,
            'academic_term_id' => $data['academic_term_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => $data['source'],
            'is_ministry' => (bool) ($data['is_ministry'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($data['source'] === Book::SOURCE_FILE && $file) {
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
            }
            $payload['file_path'] = $file->store('books', 'public');
            $payload['external_url'] = null;
        } elseif ($data['source'] === Book::SOURCE_EXTERNAL) {
            $payload['external_url'] = $data['external_url'] ?? null;
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
                $payload['file_path'] = null;
            }
        }

        if ($cover) {
            if ($book->cover_path) {
                Storage::disk('public')->delete($book->cover_path);
            }
            $payload['cover_path'] = $cover->store('books/covers', 'public');
        }

        return $this->books->update($book, $payload);
    }
}
