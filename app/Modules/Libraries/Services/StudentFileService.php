<?php

namespace App\Modules\Libraries\Services;

use App\Models\AssignmentSubmission;
use App\Models\File;
use App\Models\InternalMail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aggregates the files a student owns across the platform for the
 * "ملفاتي / My Files" tab (card #173).
 *
 * Sources that actually store a file path + an owner column:
 *   - assignment_submissions.file_path  (owner: student_id)   — no size column
 *   - files.path                        (owner: uploaded_by)  — has size + mime
 *   - internal_mails.attachment_path    (owner: sender_id)    — no size column
 *   - evaluation_evidences (file_id→files) (owner: uploaded_by) — has size + mime
 *   - support_tickets.attachment_path   (owner: created_by)   — no size column
 *
 * Note: discussion_comments has NO attachment column, so discussions are not a
 * file source and are intentionally excluded.
 */
class StudentFileService
{
    /**
     * Build the normalised list of files the student owns.
     *
     * @return Collection<int,array{key:string,source:string,title:string,type:?string,uploaded_at:?\Illuminate\Support\Carbon,size:?int,download:?string,view:?string,can_delete:bool}>
     */
    public function forStudent(User $student): Collection
    {
        $sid      = (int) $student->id;
        $schoolId = $student->school_id;
        $rows     = collect();

        // 1) Assignment submissions
        AssignmentSubmission::query()
            ->where('student_id', $sid)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->latest('submitted_at')
            ->get(['id', 'file_path', 'file_name', 'submitted_at', 'created_at'])
            ->each(function ($s) use ($rows) {
                $rows->push($this->normalise(
                    source: 'submission',
                    id: $s->id,
                    title: $s->file_name ?: basename((string) $s->file_path),
                    path: $s->file_path,
                    disk: 'public',
                    size: null,
                    uploadedAt: $s->submitted_at ?: $s->created_at,
                    canDelete: false, // graded/submitted work — read-only for the student
                ));
            });

        // 2) Generic files the student uploaded
        File::query()
            ->where('uploaded_by', $sid)
            ->latest()
            ->get(['id', 'original_name', 'name', 'path', 'disk', 'size', 'type', 'created_at'])
            ->each(function ($f) use ($rows) {
                $rows->push($this->normalise(
                    source: 'file',
                    id: $f->id,
                    title: $f->original_name ?: ($f->name ?: basename((string) $f->path)),
                    path: $f->path,
                    disk: $f->disk ?: 'public',
                    size: $f->size ?: null,
                    uploadedAt: $f->created_at,
                    canDelete: true,
                    type: $f->type,
                ));
            });

        // 3) Internal mail attachments the student sent
        InternalMail::query()
            ->where('sender_id', $sid)
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->when($schoolId, fn ($q) => $q->where(fn ($w) => $w->whereNull('school_id')->orWhere('school_id', $schoolId)))
            ->latest()
            ->get(['id', 'subject', 'attachment_path', 'created_at'])
            ->each(function ($m) use ($rows) {
                $rows->push($this->normalise(
                    source: 'mail',
                    id: $m->id,
                    title: $m->subject ?: basename((string) $m->attachment_path),
                    path: $m->attachment_path,
                    disk: 'public',
                    size: null,
                    uploadedAt: $m->created_at,
                    canDelete: false,
                ));
            });

        // 4) Evaluation evidence the student uploaded (file-backed only)
        if (class_exists(\App\Models\EvaluationEvidence::class)) {
            \App\Models\EvaluationEvidence::query()
                ->where('uploaded_by', $sid)
                ->where('type', 'file')
                ->whereNotNull('file_id')
                ->with('file:id,path,disk,original_name,size,mime_type')
                ->latest()
                ->get(['id', 'file_id', 'original_name', 'size', 'created_at'])
                ->each(function ($e) use ($rows) {
                    $file = $e->file;
                    if (! $file || ! $file->path) {
                        return;
                    }
                    $rows->push($this->normalise(
                        source: 'evidence',
                        id: $e->id,
                        title: $e->original_name ?: ($file->original_name ?: basename((string) $file->path)),
                        path: $file->path,
                        disk: $file->disk ?: 'public',
                        size: $e->size ?: $file->size ?: null,
                        uploadedAt: $e->created_at,
                        canDelete: false,
                    ));
                });
        }

        // 5) Support ticket attachments the student created
        if (class_exists(\App\Models\SupportTicket::class)) {
            \App\Models\SupportTicket::query()
                ->where('created_by', $sid)
                ->whereNotNull('attachment_path')
                ->where('attachment_path', '!=', '')
                ->when($schoolId, fn ($q) => $q->where(fn ($w) => $w->whereNull('school_id')->orWhere('school_id', $schoolId)))
                ->latest()
                ->get(['id', 'subject', 'attachment_path', 'created_at'])
                ->each(function ($t) use ($rows) {
                    $rows->push($this->normalise(
                        source: 'ticket',
                        id: $t->id,
                        title: $t->subject ?: basename((string) $t->attachment_path),
                        path: $t->attachment_path,
                        disk: 'public',
                        size: null,
                        uploadedAt: $t->created_at,
                        canDelete: false,
                    ));
                });
        }

        return $rows->sortByDesc(fn ($r) => optional($r['uploaded_at'])->timestamp ?? 0)->values();
    }

    /**
     * Download one of the student's own files. Ownership is re-verified by
     * resolving the row scoped to the student again.
     */
    public function download(User $student, string $source, int $id): Response
    {
        [$disk, $path, $name] = $this->resolveOwnedFile($student, $source, $id);

        abort_if($path === null, 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, $name ?: basename($path));
    }

    /**
     * Delete one of the student's own files. Only deletable sources (generic
     * uploads) are honoured; everything else is rejected.
     */
    public function delete(User $student, string $source, int $id): bool
    {
        if ($source !== 'file') {
            return false; // only student-owned generic uploads are deletable
        }

        $file = File::where('id', $id)->where('uploaded_by', $student->id)->first();
        if (! $file) {
            return false;
        }

        $disk = $file->disk ?: 'public';
        if ($file->path && Storage::disk($disk)->exists($file->path)) {
            Storage::disk($disk)->delete($file->path);
        }
        $file->delete();

        return true;
    }

    /**
     * Resolve a file row that the student owns into [disk, path, name].
     *
     * @return array{0:?string,1:?string,2:?string}
     */
    private function resolveOwnedFile(User $student, string $source, int $id): array
    {
        $sid = (int) $student->id;

        return match ($source) {
            'submission' => (function () use ($id, $sid) {
                $r = AssignmentSubmission::where('id', $id)->where('student_id', $sid)->first();
                return $r ? ['public', $r->file_path, $r->file_name] : [null, null, null];
            })(),
            'file' => (function () use ($id, $sid) {
                $r = File::where('id', $id)->where('uploaded_by', $sid)->first();
                return $r ? [$r->disk ?: 'public', $r->path, $r->original_name] : [null, null, null];
            })(),
            'mail' => (function () use ($id, $sid) {
                $r = InternalMail::where('id', $id)->where('sender_id', $sid)->first();
                return $r ? ['public', $r->attachment_path, null] : [null, null, null];
            })(),
            'evidence' => (function () use ($id, $sid) {
                if (! class_exists(\App\Models\EvaluationEvidence::class)) {
                    return [null, null, null];
                }
                $r = \App\Models\EvaluationEvidence::where('id', $id)->where('uploaded_by', $sid)->with('file')->first();
                return ($r && $r->file) ? [$r->file->disk ?: 'public', $r->file->path, $r->original_name] : [null, null, null];
            })(),
            'ticket' => (function () use ($id, $sid) {
                if (! class_exists(\App\Models\SupportTicket::class)) {
                    return [null, null, null];
                }
                $r = \App\Models\SupportTicket::where('id', $id)->where('created_by', $sid)->first();
                return $r ? ['public', $r->attachment_path, null] : [null, null, null];
            })(),
            default => [null, null, null],
        };
    }

    /**
     * @return array{key:string,source:string,id:int,title:string,type:?string,uploaded_at:mixed,size:?int,download:?string,view:?string,can_delete:bool,ext:string}
     */
    private function normalise(
        string $source,
        int $id,
        string $title,
        ?string $path,
        string $disk,
        ?int $size,
        $uploadedAt,
        bool $canDelete,
        ?string $type = null,
    ): array {
        $ext = $path ? Str::lower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        $view = null;
        if ($path && $disk === 'public') {
            $view = asset('storage/' . ltrim($path, '/'));
        }

        return [
            'key'         => $source . '-' . $id,
            'source'      => $source,
            'id'          => $id,
            'title'       => $title,
            'type'        => $type ?: ($ext ?: null),
            'ext'         => $ext,
            'uploaded_at' => $uploadedAt,
            'size'        => $size,
            'view'        => $view,
            'download'    => route('student.libraries.files.download', ['source' => $source, 'id' => $id]),
            'can_delete'  => $canDelete,
        ];
    }
}
