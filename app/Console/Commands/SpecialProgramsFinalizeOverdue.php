<?php

namespace App\Console\Commands;

use App\Modules\SpecialPrograms\Actions\Attempts\FinalizeOverdue;
use Illuminate\Console\Command;

class SpecialProgramsFinalizeOverdue extends Command
{
    protected $signature = 'special-programs:finalize-overdue';

    protected $description = 'Close special-program attempts whose deadline has passed (timed_out)';

    public function handle(FinalizeOverdue $action): int
    {
        $this->info('finalized: '.$action->execute());

        return self::SUCCESS;
    }
}
