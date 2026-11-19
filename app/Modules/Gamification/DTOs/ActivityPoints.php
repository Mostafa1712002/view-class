<?php

namespace App\Modules\Gamification\DTOs;

use Carbon\CarbonInterface;

/** "Target units for (rule, source, user) right now"; 0 units takes the points back. */
final readonly class ActivityPoints
{
    public function __construct(
        public string $ruleKey,
        public int $userId,
        public string $sourceType,
        public int $sourceId,
        public float $units,
        public ?int $actorId = null,
        public ?string $reason = null,
        public ?CarbonInterface $occurredAt = null,
    ) {}
}
