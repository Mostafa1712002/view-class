<?php

namespace App\Modules\SmsServices\Support;

/**
 * Shared SMS segment / message-count math (Trello #238 + #239).
 *
 * GSM-7 (Latin) single SMS = 160 chars, concatenated parts = 153 each.
 * UCS-2 (Arabic / non-Latin) single SMS = 70 chars, concatenated parts = 67.
 *
 * Used by the template editor's live counter AND — authoritatively, server
 * side — by the credit deduction at send time. Never trust a client count.
 */
final class SmsSegmentCalculator
{
    public const GSM_SINGLE = 160;
    public const GSM_MULTI  = 153;
    public const UCS_SINGLE = 70;
    public const UCS_MULTI  = 67;

    /**
     * Detect the message language bucket.
     *
     * @return 'ar'|'en'|'mixed'
     */
    public static function detectLang(string $text): string
    {
        $hasArabic = (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text);
        $hasLatin  = (bool) preg_match('/[A-Za-z]/', $text);

        if ($hasArabic && $hasLatin) {
            return 'mixed';
        }
        if ($hasArabic) {
            return 'ar';
        }

        return 'en';
    }

    /**
     * Whether the text must be encoded as UCS-2 (any non-GSM character).
     * Arabic / mixed always force UCS-2 → 70-char segments.
     */
    public static function isUnicode(string $text): bool
    {
        // Anything outside the basic GSM-7 Latin range forces UCS-2.
        return (bool) preg_match('/[^\x00-\x7F]/u', $text);
    }

    /** Character count (multibyte aware). */
    public static function length(string $text): int
    {
        return mb_strlen($text, 'UTF-8');
    }

    /**
     * Number of SMS segments the text occupies.
     */
    public static function segments(string $text): int
    {
        $len = self::length($text);
        if ($len === 0) {
            return 0;
        }

        $unicode = self::isUnicode($text);
        $single  = $unicode ? self::UCS_SINGLE : self::GSM_SINGLE;
        $multi   = $unicode ? self::UCS_MULTI  : self::GSM_MULTI;

        if ($len <= $single) {
            return 1;
        }

        return (int) ceil($len / $multi);
    }

    /** Characters remaining before the next segment boundary. */
    public static function remaining(string $text): int
    {
        $len     = self::length($text);
        $unicode = self::isUnicode($text);
        $single  = $unicode ? self::UCS_SINGLE : self::GSM_SINGLE;
        $multi   = $unicode ? self::UCS_MULTI  : self::GSM_MULTI;

        if ($len <= $single) {
            return $single - $len;
        }

        $segs = (int) ceil($len / $multi);

        return ($segs * $multi) - $len;
    }

    /**
     * Compute a full breakdown for one message body.
     *
     * @return array{length:int,segments:int,remaining:int,encoding:'gsm'|'ucs2',lang:'ar'|'en'|'mixed'}
     */
    public static function analyze(string $text): array
    {
        return [
            'length'    => self::length($text),
            'segments'  => self::segments($text),
            'remaining' => self::remaining($text),
            'encoding'  => self::isUnicode($text) ? 'ucs2' : 'gsm',
            'lang'      => self::detectLang($text),
        ];
    }
}
