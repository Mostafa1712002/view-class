<?php

namespace App\Modules\Whatsapp\Support;

class PhoneNormalizer
{
    /**
     * Normalise a raw phone/whatsapp value to digits only (with optional country
     * default). Returns null when there is no usable number.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // keep leading + then strip non-digits
        $hasPlus = str_starts_with(trim($raw), '+');
        $digits  = preg_replace('/\D+/', '', $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        return $hasPlus ? '+' . $digits : $digits;
    }

    /**
     * A number is considered valid for WhatsApp when it has a plausible length.
     * (We do not call the provider to validate; this is a cheap local sanity check.)
     */
    public static function isValid(?string $normalized): bool
    {
        if ($normalized === null) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        return strlen((string) $digits) >= 9 && strlen((string) $digits) <= 15;
    }
}
