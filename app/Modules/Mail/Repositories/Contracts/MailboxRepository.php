<?php

namespace App\Modules\Mail\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MailboxRepository
{
    /**
     * Get a paginated list of messages for the given folder.
     *
     * @param  int    $userId
     * @param  string $folder  inbox|sent|drafts|starred|important|task|archive|trash
     * @param  array  $filters Optional filters (importance, unread)
     */
    public function getFolder(int $userId, string $folder, array $filters = []): LengthAwarePaginator;

    /**
     * Return counts for all folders for the given user.
     * Keys: inbox (unread count), sent, drafts, starred, important, task, archive, trash
     */
    public function getFolderCounts(int $userId): array;

    /**
     * Mark a recipient row as read (noop if already read).
     */
    public function markRead(int $mailId, int $userId): void;

    /**
     * Paginated candidate recipients matching the compose-form filters.
     *
     * Filter keys: school_id (?int), exclude_user_id (int), group (string:
     * all|students|teachers|parents|admins|job_titles), grades (int[]),
     * classes (int[]), job_title_ids (int[]), search (?string).
     */
    public function searchRecipients(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * All recipients (id + name) matching the same filters, capped, for the
     * "select all results" action. Same filter keys as searchRecipients().
     *
     * @return array<int, array{id:int, name:string}>
     */
    public function matchingRecipients(array $filters, int $cap = 1000): array;
}
