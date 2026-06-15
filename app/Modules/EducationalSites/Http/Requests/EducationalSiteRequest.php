<?php

namespace App\Modules\EducationalSites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * #270 — Validation for create/update of an educational site.
 *
 * `url` is validated as a real URL so malformed links can never be saved —
 * this satisfies "لا توجد روابط مكسورة بدون تنبيه" at the storage boundary
 * (a bad URL is rejected with a clear Arabic error before it reaches a card).
 */
class EducationalSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware (role + permission) already gates access.
    }

    public function rules(): array
    {
        return [
            'name_ar'        => ['nullable', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'url'            => ['required', 'string', 'max:2048', 'regex:/^https?:\/\//i', 'url'],
            'category'       => ['nullable', 'string', 'max:120'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:65535'],
            'opens_new_tab'  => ['nullable', 'boolean'],
            'is_active'      => ['nullable', 'boolean'],
            'logo'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name_en' => 'اسم الموقع بالإنجليزي',
            'url'     => 'الرابط',
            'logo'    => 'الشعار',
        ];
    }

    public function messages(): array
    {
        return [
            'url.url'       => 'الرابط غير صالح. أدخل رابطًا صحيحًا يبدأ بـ http أو https.',
            'name_en.required' => 'اسم الموقع بالإنجليزي مطلوب.',
        ];
    }
}
