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
}
