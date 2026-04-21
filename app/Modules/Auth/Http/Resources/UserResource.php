<?php

namespace App\Modules\Auth\Http\Resources;

use App\Models\User;

final class UserResource
{
    public static function toArray(User $user): array
    {
        $user->loadMissing('roles', 'school.educationalCompany');

        return [
            'id' => $user->id,
            'name_ar' => $user->name_ar ?? $user->name,
            'name_en' => $user->name_en,
            'username' => $user->username,
            'email' => $user->email,
            'profilePicture' => $user->profile_picture ?? $user->avatar,
            'language_preference' => $user->language_preference ?? 'ar',
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name_ar' => $user->school->name_ar ?? $user->school->name,
                'name_en' => $user->school->name_en,
                'default_language' => $user->school->default_language ?? 'ar',
                'educational_company' => $user->school->educationalCompany ? [
                    'id' => $user->school->educationalCompany->id,
                    'name_ar' => $user->school->educationalCompany->name_ar,
                    'name_en' => $user->school->educationalCompany->name_en,
                ] : null,
            ] : null,
            'roles' => $user->roles->map(fn ($r) => [
                'id' => $r->id,
                'slug' => $r->slug,
                'name' => $r->name,
            ]),
        ];
    }
}
