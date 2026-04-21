<?php

namespace App\Modules\Profile\DTOs;

final readonly class UpdateProfileDto
{
    public function __construct(
        public ?string $nameAr,
        public ?string $nameEn,
        public ?string $phone,
        public ?string $email,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'phone' => $this->phone,
            'email' => $this->email,
        ], fn ($v) => $v !== null);
    }
}
