<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['external_id' => ['required', 'string', 'max:64']];
    }
}
