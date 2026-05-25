<?php

namespace App\Modules\NoorImport\DTOs;

final class NoorImportResult
{
    /** @param array<int, array{row:int, reason:string}> $errors */
    public function __construct(
        public readonly int $total = 0,
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $failed = 0,
        public readonly array $errors = [],
        public readonly string $status = 'completed',
        public readonly ?string $note = null,
        public readonly int $parentCreated = 0,
        public readonly int $parentUpdated = 0,
    ) {}
}
