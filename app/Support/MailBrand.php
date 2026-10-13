<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * Identity for the published mail layout (resources/views/vendor/mail).
 *
 * Read from the platform-level brand settings so every instance of this
 * codebase (الأول, رواني, ...) mails under its own name, logo and colours.
 * Settings are read directly instead of the view()->share() brand, which is
 * hard-coded to the defaults when running in the console (queue / tinker).
 */
final class MailBrand
{
    public static function get(): array
    {
        $name = $logo = $primary = $accent = null;

        try {
            $name = Setting::get('brand_name_ar', null, null);
            $logo = Setting::get('brand_logo', null, null);
            $primary = Setting::get('brand_primary_color', null, null);
            $accent = Setting::get('brand_secondary_color', null, null);
        } catch (Throwable) {
            // settings table not migrated yet — fall back to config below
        }

        if ($logo && ! preg_match('#^https?://#', $logo)) {
            $logo = asset('storage/'.ltrim($logo, '/'));
        }

        return [
            'name' => $name ?: config('mail.from.name', config('app.name')),
            'url' => config('app.url'),
            // ponytail: no logo setting = text wordmark; the bundled fallback image is another brand's logo
            'logo' => $logo ?: null,
            'logo_width' => 200,
            'primary' => self::hex($primary, '#C9A227'),
            'accent' => self::hex($accent, '#14233A'),
        ];
    }

    private static function hex(?string $value, string $default): string
    {
        return $value && preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ? $value : $default;
    }
}
