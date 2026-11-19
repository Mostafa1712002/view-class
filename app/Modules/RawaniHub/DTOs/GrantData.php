<?php

namespace App\Modules\RawaniHub\DTOs;

use Carbon\CarbonInterface;

final readonly class GrantData
{
    /** @param array<int,int> $userIds beneficiaries */
    public function __construct(
        public int $packageId,
        public array $userIds,
        public ?CarbonInterface $startsAt = null,
        public ?string $note = null,
    ) {}
}
