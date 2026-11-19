<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware role:super-admin + the Actions guard
    }

    public function rules(): array
    {
        $id = $this->route('platform');

        return [
            'slug' => ['required', 'alpha_dash', 'max:32', Rule::unique('hub_platforms', 'slug')->ignore($id)],
            'name_ar' => ['required', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
            'base_url' => ['required', 'url', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'products' => ['required', 'json'],
            'capabilities' => ['nullable', 'json'],
            'required_fields' => ['nullable', 'json'],
        ];
    }

    /** Attributes ready for SavePlatform: JSON text decoded, checkbox normalised, secret excluded. */
    public function attributes_(): array
    {
        $v = $this->validated();
        foreach (['products', 'capabilities', 'required_fields'] as $k) {
            $v[$k] = json_decode($v[$k] ?? '[]', true) ?: [];
        }
        $v['is_enabled'] = $this->boolean('is_enabled');

        return $v;
    }
}
