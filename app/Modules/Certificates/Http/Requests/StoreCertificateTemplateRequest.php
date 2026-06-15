<?php

namespace App\Modules\Certificates\Http\Requests;

use App\Models\CertificateTemplate;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateTemplateRequest extends FormRequest
{
    use HasSchoolScope;

    public function authorize(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Same fail-closed scope rule as certificates: non super-admins must
        // operate within a concrete school scope.
        return $user->isSuperAdmin() || $this->activeSchoolId() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(CertificateTemplate::TYPES)],
            'orientation' => ['required', Rule::in(CertificateTemplate::ORIENTATIONS)],
            // Card: jpg/jpeg/png/webp, < 1.5MB. Dimension mismatch is a warning,
            // not a hard failure, so it is checked (and surfaced) in the controller.
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1536'],
            'text_color' => ['nullable', 'string', 'max:16'],
            'name_color' => ['nullable', 'string', 'max:16'],
            // Body lines (شكر) — up to 5 free-text lines with placeholders.
            'lines' => ['nullable', 'array', 'max:5'],
            'lines.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
