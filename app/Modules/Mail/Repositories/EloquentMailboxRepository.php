<?php

namespace App\Modules\Mail\Repositories;

use App\Models\InternalMail;
use App\Models\InternalMailRecipient;
use App\Models\User;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentMailboxRepository implements MailboxRepository
{
    public function getFolder(int $userId, string $folder, array $filters = []): LengthAwarePaginator
    {
        $perPage = 20;

        return match ($folder) {
            'inbox'     => $this->inbox($userId, $filters, $perPage),
            'sent'      => $this->sent($userId, $filters, $perPage),
            'drafts'    => $this->drafts($userId, $filters, $perPage),
            'starred'   => $this->starred($userId, $filters, $perPage),
            'important' => $this->important($userId, $filters, $perPage),
            'task'      => $this->task($userId, $filters, $perPage),
            'archive'   => $this->archive($userId, $filters, $perPage),
            'trash'     => $this->trash($userId, $filters, $perPage),
            default     => $this->inbox($userId, $filters, $perPage),
        };
    }

    public function getFolderCounts(int $userId): array
    {
        $unreadInbox = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', false)
            ->where('archived', false)
            ->where('is_read', false)
            ->whereHas('mail', fn ($q) => $q->where('is_draft', false))
            ->count();

        $sent = InternalMail::query()
            ->where('sender_id', $userId)
            ->where('is_draft', false)
            ->count();

        $drafts = InternalMail::query()
            ->where('sender_id', $userId)
            ->where('is_draft', true)
            ->count();

        $starred = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('starred', true)
            ->where('trashed', false)
            ->count();

        $important = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', fn ($q) => $q->where('is_draft', false)->where('importance', '!=', 'normal'))
            ->count();

        $task = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('is_task', true)
            ->where('trashed', false)
            ->count();

        $archive = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('archived', true)
            ->where('trashed', false)
            ->count();

        $trash = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', true)
            ->count();

        return compact('unreadInbox', 'sent', 'drafts', 'starred', 'important', 'task', 'archive', 'trash');
    }

    public function markRead(int $mailId, int $userId): void
    {
        $row = InternalMailRecipient::query()
            ->where('mail_id', $mailId)
            ->where('recipient_id', $userId)
            ->first();

        if ($row && ! $row->is_read) {
            $row->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    // -------------------------------------------------------------------------
    // Recipient search (compose form #236)
    // -------------------------------------------------------------------------

    public function searchRecipients(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->recipientBaseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function matchingRecipients(array $filters, int $cap = 1000): array
    {
        // ponytail: capped at $cap — "select all results" grabs at most this many.
        // Raise the cap if a real school ever has more matching recipients.
        return $this->recipientBaseQuery($filters)
            ->limit($cap)
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => $u->name])
            ->all();
    }

    /**
     * The ONE query that defines who the compose form may address. Mirrors the
     * announcement targeting logic so recipient resolution cannot drift, and is
     * school-scoped to match StoreMailRequest's `to.*` exists rule exactly.
     */
    private function recipientBaseQuery(array $filters): Builder
    {
        $schoolId = $filters['school_id'] ?? null;
        $exclude  = (int) ($filters['exclude_user_id'] ?? 0);
        $group    = $filters['group'] ?? 'all';
        $grades   = $filters['grades'] ?? [];
        $classes  = $filters['classes'] ?? [];
        $jobIds   = $filters['job_title_ids'] ?? [];
        $search   = $filters['search'] ?? null;

        $query = User::query()
            ->with('roles:id,slug,name')
            ->whereNull('users.deleted_at')
            ->where('users.id', '!=', $exclude)
            // School scope MUST equal StoreMailRequest's `to.*` rule (no
            // super-admin exemption) or a shown recipient fails validation.
            ->when($schoolId, fn ($q) => $q->where('users.school_id', $schoolId));

        switch ($group) {
            case 'students':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'student'));
                $this->narrowByClassOrGrade($query, $grades, $classes);
                break;

            case 'teachers':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
                break;

            case 'parents':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'parent'));
                // Parents have no class column — narrow via the children whose
                // class/grade matches, resolved through the parent_student pivot.
                if (! empty($grades) || ! empty($classes)) {
                    $studentIds = $this->studentIdsInGradeOrClass($schoolId, $grades, $classes);
                    $parentIds  = DB::table('parent_student')
                        ->whereIn('student_id', $studentIds ?: [0])
                        ->pluck('parent_id')
                        ->all();
                    $query->whereIn('users.id', $parentIds ?: [0]);
                }
                break;

            case 'admins':
                $query->whereHas('roles', fn ($q) => $q->whereIn('slug', ['school-admin', 'super-admin']));
                break;

            case 'job_titles':
                $query->whereIn('users.job_title_id', $jobIds ?: [0]);
                break;

            case 'all':
            default:
                break;
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('users.name');
    }

    /**
     * Constrain a STUDENT query (the related table is `users` un-aliased) by
     * grade levels and/or class ids. Mirrors Announcement::applyStudentNarrowing
     * (union of the class_student pivot and the direct users.class_room_id col).
     */
    private function narrowByClassOrGrade(Builder $query, array $grades, array $classes): void
    {
        if (empty($grades) && empty($classes)) {
            return;
        }

        $query->where(function (Builder $q) use ($grades, $classes) {
            if (! empty($classes)) {
                $q->orWhereIn('users.class_room_id', $classes)
                  ->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.id', $classes));
            }
            if (! empty($grades)) {
                $q->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.grade_level', $grades))
                  ->orWhereExists(function ($sub) use ($grades) {
                      $sub->select(DB::raw(1))
                          ->from('classes')
                          ->whereColumn('classes.id', 'users.class_room_id')
                          ->whereIn('classes.grade_level', $grades);
                  });
            }
        });
    }

    /**
     * Ids of students in the given grade(s)/class(es) within the school scope,
     * used to resolve parents through the parent_student pivot.
     *
     * @return array<int>
     */
    private function studentIdsInGradeOrClass(?int $schoolId, array $grades, array $classes): array
    {
        $query = User::query()
            ->whereNull('users.deleted_at')
            ->when($schoolId, fn ($q) => $q->where('users.school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'));

        $this->narrowByClassOrGrade($query, $grades, $classes);

        return $query->pluck('users.id')->all();
    }

    // -------------------------------------------------------------------------
    // Private folder queries
    // -------------------------------------------------------------------------

    /**
     * Apply the shared mail-level filters (importance + free-text search on
     * subject/body) to a query already constrained to the `mail` relation.
     */
    private function applyMailFilters($q, array $filters): void
    {
        if (! empty($filters['importance'])) {
            $q->where('importance', $filters['importance']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $q->where(function ($w) use ($term) {
                $w->where('subject', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%");
            });
        }
    }

    private function inbox(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function sent(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMail::query()
            ->with(['recipients.recipient:id,name'])
            ->where('sender_id', $uid)
            ->where('is_draft', false);

        $this->applyMailFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function drafts(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMail::query()
            ->where('sender_id', $uid)
            ->where('is_draft', true);

        $this->applyMailFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function starred(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('starred', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function important(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false)->where('importance', '!=', 'normal');
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function task(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('is_task', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function archive(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('archived', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function trash(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', true)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                $this->applyMailFilters($q, $filters);
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }
}
