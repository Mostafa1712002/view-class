<?php

namespace App\Modules\Promotion\Actions;

use App\Models\PromotionBatch;
use App\Models\School;
use App\Modules\Promotion\Repositories\Contracts\PromotionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Undo a promotion batch (US-011). Only the LATEST executed batch of a school
 * may be rolled back — reversing an older one could fight a newer batch's
 * changes. Each item carries its own before-state, so restoring is exact.
 */
class RollbackPromotionAction
{
    public function __construct(private PromotionRepository $repo) {}

    public function execute(School $school, PromotionBatch $batch, string $password): PromotionBatch
    {
        $actor = auth()->user();

        if (! $actor || ! Hash::check($password, $actor->password)) {
            throw ValidationException::withMessages([
                'password' => __('schools.promotion_bad_password'),
            ]);
        }

        if ($batch->school_id !== $school->id || $batch->status !== 'executed') {
            throw new RuntimeException(__('schools.promotion_rollback_invalid'));
        }

        $newer = PromotionBatch::where('school_id', $school->id)
            ->where('status', 'executed')
            ->where('id', '>', $batch->id)
            ->exists();

        if ($newer) {
            throw new RuntimeException(__('schools.promotion_rollback_not_latest'));
        }

        return DB::transaction(function () use ($batch, $actor) {
            foreach ($batch->items()->orderByDesc('id')->get() as $item) {
                $student = (int) $item->student_id;

                switch ($item->action) {
                    case 'promoted':
                    case 'overflow_moved':
                        if ($item->to_class_id) {
                            $this->repo->detachStudent((int) $item->to_class_id, $student);
                        }
                        if ($item->from_class_id) {
                            $this->repo->attachStudent((int) $item->from_class_id, $student, 'passed');
                        }
                        $this->repo->setPlacement($student, $item->from_section_id ? (int) $item->from_section_id : null, $item->from_class_id ? (int) $item->from_class_id : null);
                        break;

                    case 'graduated':
                        $this->repo->setGraduated($student, false);
                        if ($item->from_class_id) {
                            $this->repo->attachStudent((int) $item->from_class_id, $student, 'passed');
                        }
                        $this->repo->setPlacement($student, $item->from_section_id ? (int) $item->from_section_id : null, $item->from_class_id ? (int) $item->from_class_id : null);
                        break;

                    case 'not_moved':
                        break;
                }
            }

            $batch->update([
                'status' => 'rolled_back',
                'rolled_back_by' => $actor->id,
                'rolled_back_at' => now(),
            ]);

            return $batch;
        });
    }
}
