<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Turns an image reference from the import sheet into a path on the public disk
 * (what the question views render via Storage::disk('public')->url()).
 *
 * A reference is either "public:<path>" (a picture embedded in the sheet, already
 * stored while parsing) or an http(s) URL, which is downloaded here at confirm time.
 */
final class ResolveImportImage
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const MIME_EXT = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
        'image/webp' => 'webp', 'image/svg+xml' => 'svg',
    ];

    public static function isEmbedded(string $ref): bool
    {
        return str_starts_with($ref, 'public:');
    }

    /** @throws \RuntimeException with an Arabic message when the image can't be used. */
    public function execute(string $ref, int $bankId): string
    {
        if (self::isEmbedded($ref)) {
            return substr($ref, 7);
        }

        $parts = parse_url($ref);
        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw new \RuntimeException("رابط الصورة غير صالح: {$ref}");
        }
        $ip = gethostbyname($parts['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP) && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \RuntimeException("رابط الصورة غير مسموح به: {$ref}");
        }

        try {
            $response = Http::timeout(15)->withoutRedirecting()->get($ref);
        } catch (\Throwable $e) {
            throw new \RuntimeException("تعذر تحميل الصورة: {$ref}");
        }
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $body = $response->body();
        if (! $response->successful() || ! isset(self::MIME_EXT[$mime]) || $body === '' || strlen($body) > self::MAX_BYTES) {
            throw new \RuntimeException("تعذر تحميل الصورة أو أنها ليست صورة صالحة (حتى 5 ميجابايت): {$ref}");
        }

        $path = "bank-questions/{$bankId}/".bin2hex(random_bytes(10)).'.'.self::MIME_EXT[$mime];
        Storage::disk('public')->put($path, $body);

        return $path;
    }
}
