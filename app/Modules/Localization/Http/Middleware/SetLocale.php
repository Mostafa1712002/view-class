<?php

namespace App\Modules\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    private const SUPPORTED = ['ar', 'en'];
    private const DEFAULT = 'ar';

    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        if ($user = $request->user()) {
            if (in_array($user->language_preference, self::SUPPORTED, true)) {
                return $user->language_preference;
            }
        }

        $sessionLocale = $request->session()->get('locale');
        if (in_array($sessionLocale, self::SUPPORTED, true)) {
            return $sessionLocale;
        }

        return self::DEFAULT;
    }
}
