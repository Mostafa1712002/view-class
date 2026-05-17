<?php

namespace App\Modules\NoorImport\DTOs;

/**
 * One row coming out of a Noor Excel/CSV file, after we have mapped
 * the Arabic Noor header names to a stable internal key set.
 */
final class NoorRowDto
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $nationalId,
        public readonly ?string $academicNumber,
        public readonly ?string $name,
        public readonly ?string $gender,
        public readonly ?string $birthDate,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $classRoom,
        public readonly ?string $specialization,
        public readonly array $raw = [],
    ) {}
}
