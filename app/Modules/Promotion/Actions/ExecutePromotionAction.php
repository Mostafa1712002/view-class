<?php

namespace App\Modules\Promotion\Actions;

use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\School;
use App\Models\User;
use App\Modules\Promotion\Notifications\ClassCapacityExceeded;
use App\Modules\Promotion\Repositories\Contracts\PromotionRepository;
use App\Modules\Promotion\Services\PromotionPlanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Apply a promotion plan (US-010). Password-gated and fully transactional: the
 * plan is recomputed under the transaction, every move is written as a
 * promotion_batch_item (its own before/after record), and the school admins are
 * notified of any unplaced overflow. One un-rolled-back batch per (school,
 * src→dst) is allowed — re-running requires rolling the previous one back.
 */
class ExecutePromotionAction
{
    public function __construct(
        private PromotionPlanner $planner,
        private PromotionRepository $repo,
    ) {}

    public function execute(School $school, AcademicYear $src, AcademicYear $dst, string $password): PromotionBatch
    {
        $actor = auth()->user();

        if (! $actor || ! Hash::check($password, $actor->password)) {
            throw ValidationException::withMessages([
                'password' => __('schools.promotion_bad_password'),
            ]);
        }

        if ($src->id === $dst->id) {
            throw new RuntimeException(__('schools.promotion_same_year'));
        }

        $duplicate = PromotionBatch::where('school_id', $school->id)
            ->where('source_year_id', $src->id)
            ->where('destination_year_id', $dst->id)
            ->where('status', 'executed')
            ->exists();

        if ($duplicate) {
            throw new RuntimeException(__('schools.promotion_already_run'));
        }

        return DB::transaction(function () use ($school, $src, $dst, $actor) {
            $plan = $this->planner->plan($school, $src, $dst);

            if (! empty($plan['blockers'])) {
                throw new RuntimeException(implode(' • ', $plan['blockers']));
            }

            $batch = PromotionBatch::create([
                'school_id' => $school->id,
                'source_year_id' => $src->id,
                'destination_year_id' => $dst->id,
                'status' => 'executed',
                'summary' => $plan['summary'],
                'executed_by' => $actor->id,
                'executed_at' => now(),
            ]);

            foreach ($plan['moves'] as $move) {
                $this->applyMove($move);
                PromotionBatchItem::create([
                    'batch_id' => $batch->id,
                    'student_id' => $move['student_id'],
                    'action' => $move['action'],
                    'from_section_id' => $move['from_section_id'],
                    'from_class_id' => $move['from_class_id'],
                    'to_section_id' => $move['to_section_id'],
                    'to_class_id' => $move['to_class_id'],
                    'reason' => $move['reason'],
                ]);
            }

            $this->notifyPrincipals($school, $plan['capacity_alerts']);

            return $batch;
        });
    }

    private function applyMove(array $move): void
    {
        $student = (int) $move['student_id'];

        switch ($move['action']) {
            case 'promoted':
            case 'overflow_moved':
                if ($move['from_class_id']) {
                    $this->repo->detachStudent((int) $move['from_class_id'], $student);
                }
                $this->repo->attachStudent((int) $move['to_class_id'], $student, 'pending');
                $this->repo->setPlacement($student, (int) $move['to_section_id'], (int) $move['to_class_id']);
                break;

            case 'graduated':
                if ($move['from_class_id']) {
                    $this->repo->detachStudent((int) $move['from_class_id'], $student);
                }
                // Keep the final grade visible; drop the class; flag graduation.
                $this->repo->setPlacement($student, $move['from_section_id'] ? (int) $move['from_section_id'] : null, null);
                $this->repo->setGraduated($student, true);
                break;

            case 'not_moved':
                // Student stays exactly where they are — no enrollment change.
                break;
        }
    }

    /** @param array<int,array{grade:string,number:int,count:int}> $alerts */
    private function notifyPrincipals(School $school, array $alerts): void
    {
        if (empty($alerts)) {
            return;
        }

        $admins = User::where('school_id', $school->id)
            ->whereHas('roles', fn ($r) => $r->where('slug', 'school-admin'))
            ->get();

        foreach ($admins as $admin) {
            foreach ($alerts as $alert) {
                $admin->notify(new ClassCapacityExceeded($alert['grade'], $alert['number'], $alert['count']));
            }
        }
    }
}
