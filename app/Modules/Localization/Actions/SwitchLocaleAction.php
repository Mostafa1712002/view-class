<?php

namespace App\Modules\Localization\Actions;

use App\Models\User;

final class SwitchLocaleAction
{
    public const SUPPORTED = ['ar', 'en'];

    public function execute(?User $user, string $locale, \Illuminate\Session\Store $session): string
    {
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        $session->put('locale', $locale);
        app()->setLocale($locale);

        if ($user instanceof User) {
            $user->forceFill(['language_preference' => $locale])->save();
        }

        return $locale;
    }
}
