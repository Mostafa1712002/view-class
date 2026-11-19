<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** external_id resolves a person's `conflict` row; user_id links an unlinked directory row to a Rawani person. */
class LinkAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_id' => ['required_without:user_id', 'prohibits:user_id', 'nullable', 'string', 'max:64'],
            'user_id' => ['required_without:external_id', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
