<?php

namespace App\Modules\RawaniHub\DTOs;

final readonly class PackageData
{
    /** @param array<int, array{platform_id:int, product_code:string}> $items */
    public function __construct(
        public string $nameAr,
        public float|string $price, // VAT-inclusive (15%), SAR
        public int $durationDays,
        public array $items,
        public ?string $nameEn = null,
        public ?string $descriptionAr = null,
        public string $currency = 'SAR',
        public bool $isPurchasable = false,
        public bool $isActive = true,
        public int $sort = 0,
    ) {}
}
