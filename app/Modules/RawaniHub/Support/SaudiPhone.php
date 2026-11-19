<?php

namespace App\Modules\RawaniHub\Support;

/**
 * One canonical form for directory phone matching: Saudi mobiles become 9665XXXXXXXX whether typed
 * 05…, 5…, 9665…, +9665…, 009665… or +966 05…; any other number is kept as its digits.
 */
final class SaudiPhone
{
    public static function canonical(?string $raw): ?string
    {
        $digits = preg_replace('/^00/', '', preg_replace('/\D+/', '', (string) $raw));
        if ($digits === '') {
            return null;
        }

        return preg_match('/^(?:9660|966|0)?(5\d{8})$/', $digits, $m) ? '966'.$m[1] : $digits;
    }

    /**
     * The raw spellings a free-typed column (users.phone) may hold for this canonical number, for an indexed whereIn.
     * ponytail: spaced or dashed spellings ("050 123 4567") are not covered; normalise users.phone on write if they show up.
     *
     * @return list<string>
     */
    public static function spellings(string $canonical): array
    {
        if (! preg_match('/^966(5\d{8})$/', $canonical, $m)) {
            return [$canonical, '+'.$canonical];
        }

        return ['0'.$m[1], $m[1], $canonical, '+'.$canonical, '00'.$canonical, '+9660'.$m[1]];
    }
}
