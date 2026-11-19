<?php

namespace App\Modules\Gamification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the controller authorizes against the target user
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'between:-100000,100000', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
