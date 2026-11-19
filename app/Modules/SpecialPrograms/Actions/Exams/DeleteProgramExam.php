<?php

namespace App\Modules\SpecialPrograms\Actions\Exams;

use App\Models\User;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\SpecialPrograms\Models\SpExam;
use Illuminate\Support\Facades\DB;

/**
 * Soft delete only: attempts stay, the exam leaves lists and reports. Creator or school-admin.
 * Points awarded for its attempts are taken back, like every other deleted points source.
 */
final class DeleteProgramExam
{
    public function __construct(private RevokeSourcePoints $revoke) {}

    public function execute(SpExam $exam, User $user): void
    {
        abort_unless($user->isSuperAdmin() || $user->isSchoolAdmin() || (int) $exam->created_by === (int) $user->id, 403);
        $exam->delete();

        $attemptIds = DB::table('sp_attempts')->where('exam_id', $exam->id)->pluck('id');
        rescue(function () use ($attemptIds) {
            foreach ($attemptIds as $id) {
                $this->revoke->execute('program_test', (int) $id, 'exam deleted');
            }
        }, null, true);
    }
}
