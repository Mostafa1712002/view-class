<?php

namespace App\Modules\SpecialEducation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialEducationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body'      => ['required', 'string'],
            'note_date' => ['nullable', 'date'],
        ];
    }
}
