<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth-login', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
        ]);

        // Share platform brand settings with all Blade views.
        // school_id = null reads the platform-level (super-admin) brand row.
        // Falls back to hardcoded defaults so the app works before migration runs.
        $this->shareGlobalBrand();
    }

    private function shareGlobalBrand(): void
    {
        // Skip DB hit during artisan commands that don't render views
        if ($this->app->runningInConsole()) {
            view()->share([
                'brand_name_ar'         => 'المنصة الذهبية',
                'brand_name_en'         => 'Golden Platform',
                'brand_logo'            => '',
                'brand_favicon'         => '',
                'brand_primary_color'   => '#C9A227',
                'brand_secondary_color' => '#14233A',
                'brand_font'            => 'Cairo',
            ]);
            return;
        }

        try {
            $brand = Cache::remember('platform_brand', 3600, function () {
                return [
                    'brand_name_ar'         => Setting::get('brand_name_ar', 'المنصة الذهبية', null),
                    'brand_name_en'         => Setting::get('brand_name_en', 'Golden Platform',  null),
                    'brand_logo'            => Setting::get('brand_logo',    '',                 null),
                    'brand_favicon'         => Setting::get('brand_favicon', '',                 null),
                    'brand_primary_color'   => Setting::get('brand_primary_color',   '#C9A227',  null),
                    'brand_secondary_color' => Setting::get('brand_secondary_color', '#14233A',  null),
                    'brand_font'            => Setting::get('brand_font',             'Cairo',   null),
                ];
            });
        } catch (\Exception $e) {
            // DB not yet migrated (fresh install) — use hardcoded defaults
            $brand = [
                'brand_name_ar'         => 'المنصة الذهبية',
                'brand_name_en'         => 'Golden Platform',
                'brand_logo'            => '',
                'brand_favicon'         => '',
                'brand_primary_color'   => '#C9A227',
                'brand_secondary_color' => '#14233A',
                'brand_font'            => 'Cairo',
            ];
        }

        view()->share($brand);
    }
}
