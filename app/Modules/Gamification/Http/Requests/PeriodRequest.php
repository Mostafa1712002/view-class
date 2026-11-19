<?php

namespace App\Modules\Gamification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces manageRewards
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'section_id' => ['nullable', 'integer'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'min_points' => ['required', 'integer', 'min:1', 'max:10000000'],
        ];
    }
}
