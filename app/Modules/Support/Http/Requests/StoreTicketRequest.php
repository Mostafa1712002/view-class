<?php

namespace App\Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:40'],
            'subject'  => ['required', 'string', 'max:160'],
            'body'     => ['required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ];
    }
}
