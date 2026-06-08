<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Models\EvaluationTarget;
use App\Models\User;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * Task 6 — add/remove the users an evaluation form targets (who is evaluated).
 *
 * Targets are stored polymorphically as target_type='user'. The (form, type, id)
 * unique index prevents the same user being targeted twice; we add only the users
 * that are not already targeted so a re-submit is idempotent and never throws.
 */
class SetEvaluationTargets
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * Add the given user ids as targets, skipping any already present.
     *
     * @param  int[]  $userIds
     * @return int  number of targets newly added
     */
    public function add(EvaluationForm $form, array $userIds, int $actorId): int
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return 0;
        }

        return DB::transaction(function () use ($form, $userIds, $actorId) {
            $existing = $form->targets()
                ->where('target_type', 'user')
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $toAdd = array_diff($userIds, $existing);
            if ($toAdd === []) {
                return 0;
            }

            // Only persist users that actually exist (and load org meta for filters).
            $users = User::query()
                ->whereIn('id', $toAdd)
                ->with(['subjects:id', 'section:id', 'classRoom:id'])
                ->get()
                ->keyBy('id');

            $added       = 0;
            $afterPublish = $form->status?->value === 'published';

            foreach ($toAdd as $userId) {
                /** @var User|null $user */
                $user = $users->get($userId);
                if (!$user) {
                    continue;
                }

                $target = EvaluationTarget::firstOrCreate(
                    ['form_id' => $form->id, 'target_type' => 'user', 'target_id' => $userId],
                    [
                        'meta' => [
                            'school_id' => $user->school_id,
                            'stage'     => $user->section_id,
                            'subject'   => $user->subjects->pluck('id')->all(),
                        ],
                        'added_after_publish' => $afterPublish,
                        'added_by'            => $actorId,
                    ]
                );

                if ($target->wasRecentlyCreated) {
                    $added++;
                    $this->audit->record(
                        'target.add',
                        "إضافة مستهدف للنموذج «{$form->title}»: {$user->name}",
                        $target,
                        null,
                        $target->toArray()
                    );
                }
            }

            return $added;
        });
    }

    /** Remove a single target; cascades its assignment links. */
    public function remove(EvaluationForm $form, int $targetId, int $actorId): bool
    {
        $target = $form->targets()->whereKey($targetId)->first();
        if (!$target) {
            return false;
        }

        return (bool) DB::transaction(function () use ($form, $target) {
            // Detach from any evaluator assignment scopes first.
            DB::table('evaluation_assignment_targets')->where('target_id', $target->id)->delete();

            $this->audit->record(
                'target.remove',
                "حذف مستهدف من النموذج «{$form->title}» (#{$target->target_id})",
                $target,
                $target->toArray(),
                null
            );

            return $target->delete();
        });
    }
}
