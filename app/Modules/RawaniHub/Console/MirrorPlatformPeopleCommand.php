<?php

namespace App\Modules\RawaniHub\Console;

use App\Modules\RawaniHub\Jobs\MirrorPlatformPeopleJob;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Console\Command;
use Throwable;

/** Starts (or resumes) the directory mirror walk of each enabled platform (design §3.1, task 4.1). */
class MirrorPlatformPeopleCommand extends Command
{
    protected $signature = 'hub:mirror
        {--platform= : one platform slug}
        {--full : at the end of the walk, mark rows the platform no longer lists as missing (weekly)}
        {--sync : walk inline instead of queueing one job per page}
        {--per=500 : page size}';

    protected $description = 'Mirror every platform account (GET /people) into the Rawani directory';

    public function handle(PlatformRepository $platforms): int
    {
        $slug = $this->option('platform');
        $list = $slug ? array_filter([$platforms->findBySlug($slug)]) : $platforms->enabled()->all();
        if (! $list) {
            $this->error('no platform');

            return self::FAILURE;
        }

        $failed = false;
        foreach ($list as $platform) {
            if (! $platform->is_enabled || ! $platform->secret) {
                $this->warn("{$platform->slug}: disabled or no secret, skipped");

                continue;
            }
            if (! $platform->mirror_started_at) {
                $platforms->update($platform, ['mirror_started_at' => now()->startOfSecond(), 'mirror_cursor' => null, 'mirror_full' => (bool) $this->option('full')]);
            } elseif ($this->option('full') && ! $platform->mirror_full) {
                $platforms->update($platform, ['mirror_full' => true]);
            }
            $job = new MirrorPlatformPeopleJob($platform->id, $platform->mirror_started_at->format('Y-m-d H:i:s'), $platform->mirror_cursor, (bool) $this->option('sync'), max(1, min(500, (int) $this->option('per'))));

            try {
                $this->option('sync') ? dispatch_sync($job) : dispatch($job);
                $this->info($platform->slug.': '.($this->option('sync') ? 'done' : 'queued').($platform->mirror_cursor ? " (resumed at {$platform->mirror_cursor})" : ''));
            } catch (Throwable $e) {
                $failed = true;
                $this->error("{$platform->slug}: {$e->getMessage()} (resumes from the stored cursor next run)");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
