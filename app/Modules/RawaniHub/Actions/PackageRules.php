<?php

namespace App\Modules\RawaniHub\Actions;

use App\Modules\RawaniHub\DTOs\PackageData;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Validation\ValidationException;

/** Shared invariants for CreatePackage / UpdatePackage. */
trait PackageRules
{
    private function validatePackage(PackageData $d, PlatformRepository $platforms): void
    {
        $e = [];
        if (trim($d->nameAr) === '') {
            $e['name_ar'] = 'required';
        }
        if (! is_numeric($d->price) || (float) $d->price < 0) {
            $e['price'] = 'must be a number >= 0';
        } elseif ($d->isPurchasable && (float) $d->price <= 0) {
            $e['price'] = 'a purchasable package needs a price above 0';
        }
        if ($d->durationDays < 1) {
            $e['duration_days'] = 'must be >= 1';
        }
        if (strlen($d->currency) !== 3) {
            $e['currency'] = 'must be a 3-letter code';
        }
        if ($d->items === []) {
            $e['items'] = 'at least one platform product';
        }
        $seen = [];
        foreach ($d->items as $i => $item) {
            $key = ($item['platform_id'] ?? '').'|'.($item['product_code'] ?? '');
            $platform = $platforms->find((int) ($item['platform_id'] ?? 0));
            $codes = array_column($platform?->products ?? [], 'code');
            if (! $platform || ! in_array($item['product_code'] ?? null, $codes, true)) {
                $e["items.$i"] = 'unknown platform or product';
            } elseif (isset($seen[$key])) {
                $e["items.$i"] = 'duplicate item';
            }
            $seen[$key] = true;
        }
        if ($e) {
            throw ValidationException::withMessages($e);
        }
    }

    private function packageAttributes(PackageData $d): array
    {
        return [
            'name_ar' => trim($d->nameAr), 'name_en' => $d->nameEn, 'description_ar' => $d->descriptionAr,
            'price' => number_format((float) $d->price, 2, '.', ''), 'currency' => strtoupper($d->currency),
            'duration_days' => $d->durationDays, 'is_purchasable' => $d->isPurchasable,
            'is_active' => $d->isActive, 'sort' => $d->sort,
        ];
    }
}
