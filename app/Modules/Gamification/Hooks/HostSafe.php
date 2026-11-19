<?php

namespace App\Modules\Gamification\Hooks;

use Illuminate\Support\Facades\DB;

/**
 * Host hooks never change the host: their work runs after the host transaction commits (dropped
 * when it rolls back) and any failure is reported, never thrown into the host request.
 */
final class HostSafe
{
    public static function run(\Closure $work): void
    {
        rescue(fn () => DB::afterCommit(fn () => rescue($work, null, true)), null, true);
    }
}
