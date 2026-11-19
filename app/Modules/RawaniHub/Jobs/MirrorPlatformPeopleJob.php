<?php

namespace App\Modules\RawaniHub\Jobs;

use App\Models\ActivityLog;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\PlatformClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/**
 * Mirrors one GET /people page of a platform into hub_platform_accounts, then queues the next page (design §3.1, §4.3).
 * The walk state (run id = mirror_started_at, cursor) lives on hub_platforms, so a failed or lost job only parks the walk:
 * the next `hub:mirror` resumes at the stored cursor. A stale or duplicate job sees a different cursor and stops.
 * Inline mode (`hub:mirror --sync`) loops the pages in-process instead of queueing them.
 */
class MirrorPlatformPeopleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const PER = 500;

    public int $tries = 5;

    public int $timeout = 120;

    public function __construct(
        public int $platformId,
        public string $run,
        public ?string $cursor = null,
        public bool $inline = false,
        public int $per = self::PER,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(PlatformRepository $platforms, PlatformAccountRepository $accounts, AccountSyncDispatcher $sync): void
    {
        $cursor = $this->cursor;
        do {
            $platform = $platforms->find($this->platformId);
            if (! $platform || ! $platform->is_enabled || ! $platform->secret
                || $platform->mirror_started_at?->format('Y-m-d H:i:s') !== $this->run || $platform->mirror_cursor !== $cursor) {
                return; // switched off, or this copy is stale
            }

            $result = (new PlatformClient((string) $platform->base_url, (string) $platform->secret))->people($cursor, $this->per);
            if (! $result->ok()) {
                $error = mb_substr('mirror: '.($result->code ?? $result->status).' '.($result->message ?? ''), 0, 500);
                if ($result->isRetryable()) {
                    throw new RuntimeException($error); // queue backoff; the cursor is unchanged
                }
                $platforms->update($platform, ['last_error' => $error]); // 401/4xx do not heal by retrying

                return;
            }

            $revived = $accounts->mirrorPage($platform->id, (array) ($result->data['items'] ?? []), now());
            $sync->afterCommit($revived);

            $next = $result->data['next_cursor'] ?? null;
            $next = ($next === null || $next === '') ? null : (string) $next;
            if ($next === null && $platform->mirror_full) {
                $missing = $accounts->sweepMissing($platform->id, $platform->mirror_started_at);
                ActivityLog::log('hub.mirror.sweep', "دليل المستخدمين: {$missing} حساب مفقود على المنصة #{$platform->id}", $platform, null, ['missing' => $missing]);
            }
            if (! $platforms->advanceMirror($platform->id, $this->run, $cursor, $next) || $next === null) {
                return; // another copy moved on first, or the walk is complete
            }
            $cursor = $next;
        } while ($this->inline);

        dispatch(new self($this->platformId, $this->run, $cursor, false, $this->per));
    }
}
