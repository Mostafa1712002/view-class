<?php

namespace App\Modules\SpecialEducation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialEducationPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:160'],
            'goals'          => ['nullable', 'string'],
            'accommodations' => ['nullable', 'string'],
            'start_date'     => ['nullable', 'date'],
            'review_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'         => ['required', 'string', 'in:draft,active,completed'],
        ];
    }
}
