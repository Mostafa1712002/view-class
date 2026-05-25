<?php

namespace App\Modules\Subjects\DTOs;

/**
 * Outcome of an Excel subjects import.
 *
 * @property array<int, array{row:int, reason:string}> $errors
 */
final class SubjectImportResult
{
    /** @param array<int, array{row:int, reason:string}> $errors */
    public function __construct(
        public readonly int $total = 0,
        public readonly int $created = 0,
        public readonly int $skipped = 0,
        public readonly int $failed = 0,
        public readonly array $errors = [],
    ) {}
}
