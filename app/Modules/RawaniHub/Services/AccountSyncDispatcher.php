<?php

namespace App\Modules\RawaniHub\Services;

use Illuminate\Support\Facades\DB;

/**
 * Dispatches SyncPlatformAccountJob for the given accounts once the surrounding transaction commits.
 * The job itself ships with Task 1.5; until it exists this is a no-op (hub:sync-pending picks up needs_sync rows).
 */
class AccountSyncDispatcher
{
    public const JOB = \App\Modules\RawaniHub\Jobs\SyncPlatformAccountJob::class;

    /** @param array<int,int> $accountIds */
    public function afterCommit(array $accountIds): void
    {
        if (! class_exists(self::JOB)) {
            return;
        }
        DB::afterCommit(function () use ($accountIds) {
            foreach (array_unique($accountIds) as $id) {
                dispatch(new (self::JOB)($id));
            }
        });
    }
}
