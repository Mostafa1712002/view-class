<?php

namespace App\Modules\RawaniHub\DTOs;

final readonly class BeneficiaryData
{
    public function __construct(
        public string $name,
        public string $gender, // male|female
        public string $nationalId,
        public string $stage, // primary|intermediate|secondary
        public int $grade,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $parentName = null,
        public ?string $parentNationalId = null,
        public ?string $parentGender = null, // male|female, required when a NEW parent is created
    ) {}
}
