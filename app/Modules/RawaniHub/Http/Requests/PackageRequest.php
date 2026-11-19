<?php

namespace App\Modules\RawaniHub\Http\Requests;

use App\Modules\RawaniHub\DTOs\PackageData;
use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string', 'regex:/^\d+\|[\w.-]+$/'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function toData(): PackageData
    {
        $items = array_map(function (string $v) {
            [$platform, $code] = explode('|', $v, 2);

            return ['platform_id' => (int) $platform, 'product_code' => $code];
        }, $this->validated('items'));

        return new PackageData(
            nameAr: $this->validated('name_ar'), price: $this->validated('price'),
            durationDays: (int) $this->validated('duration_days'), items: array_values($items),
            nameEn: $this->validated('name_en'), descriptionAr: $this->validated('description_ar'),
            isPurchasable: $this->boolean('is_purchasable'), isActive: $this->boolean('is_active'),
            sort: (int) $this->validated('sort'),
        );
    }
}
