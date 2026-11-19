<?php

namespace App\Modules\RawaniHub\Console;

use App\Modules\RawaniHub\Jobs\SyncPlatformAccountJob;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use Illuminate\Console\Command;

/** Recovers lost jobs: re-dispatches dirty accounts whose last attempt is over 10 minutes old (design §3.3). */
class SyncPendingAccountsCommand extends Command
{
    protected $signature = 'hub:sync-pending';

    protected $description = 'Re-dispatch sync jobs for dirty hub platform accounts';

    public function handle(PlatformAccountRepository $accounts): int
    {
        $rows = $accounts->dirtyForSync(now()->subMinutes(10));
        foreach ($rows as $row) {
            dispatch(new SyncPlatformAccountJob($row->id));
        }
        $this->info("dispatched {$rows->count()}");

        return self::SUCCESS;
    }
}
