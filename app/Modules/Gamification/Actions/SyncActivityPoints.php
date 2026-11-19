<?php

namespace App\Modules\Gamification\Actions;

use App\Models\User;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;
use App\Modules\Gamification\Support\RuleCatalog;
use App\Modules\Gamification\Support\RuleSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * PUBLIC CONTRACT. Syncs one ledger key (rule, source, user) to the target for the given units and
 * writes only the difference. Idempotent, runs after the surrounding transaction commits and never
 * throws into the caller (failures are reported).
 */
final class SyncActivityPoints
{
    public function __construct(
        private PointLedgerRepository $ledger,
        private RewardRepository $rewards,
        private RuleSettings $settings,
        private CheckRewardThreshold $checkReward,
    ) {}

    public function execute(ActivityPoints $dto): void
    {
        DB::afterCommit(fn () => rescue(fn () => $this->run($dto), null, true));
    }

    private function run(ActivityPoints $dto): void
    {
        $rule = RuleCatalog::get($dto->ruleKey);
        if (! $rule) {
            return;
        }

        // Concurrent first awards end in a deadlock (retried by DB::transaction) or a unique violation: re-read once.
        try {
            $written = DB::transaction(fn () => $this->write($dto, $rule), 3);
        } catch (UniqueConstraintViolationException) {
            $written = DB::transaction(fn () => $this->write($dto, $rule), 3);
        }

        if ($written && $rule['beneficiary'] === RuleCatalog::STUDENT && $written['delta'] > 0) {
            $this->checkReward->execute($dto->userId, $dto->occurredAt);
        }
    }

    /** @return array{delta:int}|null */
    private function write(ActivityPoints $dto, array $rule): ?array
    {
        $rows = $this->ledger->keyRowsForUpdate($dto->userId, $dto->ruleKey, $dto->sourceType, $dto->sourceId);
        // A key that already has rows keeps following its source even if the user was deleted since.
        $user = $this->ledger->findUser($dto->userId, withTrashed: $rows->isNotEmpty());
        if (! $user) {
            return null;
        }

        $net = (int) $rows->sum('points');
        $at = $dto->occurredAt ?? now();
        $units = max(0.0, $dto->units);

        if ($rows->isEmpty()) {
            if (! $this->matchesBeneficiary($user, $rule['beneficiary'])) {
                return null;
            }
            $settings = $this->settings->effective($dto->ruleKey, (int) $user->school_id);
            if (! $settings['enabled'] || $settings['points'] === 0 || $units == 0.0) {
                return null;
            }
            $period = $this->rewards->activePeriods((int) $user->school_id, $user->section_id, $at)->first();
            $unitPoints = ($period && $settings['period_points'] !== null) ? (int) $settings['period_points'] : $settings['points'];
            $periodId = $period?->id;
            $basis = $settings['options']['basis'] ?? null;
        } else {
            // Re-price at the first row's value: later rule changes do not silently re-price old work.
            $meta = $rows->first()->meta ?? [];
            $unitPoints = (int) ($meta['unit_points'] ?? 0);
            $periodId = $meta['period_id'] ?? null;
            $basis = $meta['basis'] ?? null;
        }

        $target = (int) round($units * $unitPoints);
        $delta = $target - $net;
        if ($delta === 0) {
            return null;
        }

        $this->ledger->insert([
            'school_id' => (int) $user->school_id,
            'user_id' => $user->id,
            'rule_key' => $dto->ruleKey,
            'source_type' => $dto->sourceType,
            'source_id' => $dto->sourceId,
            'revision' => $rows->count(),
            'points' => $delta,
            'kind' => $rows->isEmpty() ? 'award' : ($target === 0 ? 'reverse' : 'adjust'),
            'reason' => $dto->reason,
            'meta' => ['units' => $units, 'unit_points' => $unitPoints, 'calc_mode' => $rule['calc_mode'],
                'basis' => $basis, 'period_id' => $periodId],
            'created_by' => $dto->actorId,
            'created_at' => $at,
        ]);

        return ['delta' => $delta];
    }

    /** vice is checked first: وكلاء carry the school-admin role and may also be teachers. */
    private function matchesBeneficiary(User $user, string $beneficiary): bool
    {
        return match ($beneficiary) {
            RuleCatalog::VICE => $user->jobTitle?->slug === 'vice',
            RuleCatalog::TEACHER => $user->isTeacher(),
            RuleCatalog::STUDENT => $user->isStudent(),
            default => false,
        };
    }
}
