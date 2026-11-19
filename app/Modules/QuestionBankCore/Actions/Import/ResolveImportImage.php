<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Turns an image reference from the import sheet into a path on the public disk
 * (what the question views render via Storage::disk('public')->url()).
 *
 * A reference is either "public:<path>" (a picture embedded in the sheet, stored by
 * the parser itself — never typed by the teacher) or an http(s) URL, downloaded here
 * at confirm time. The URL fetch is SSRF-hardened: every A/AAAA record must be a
 * public address, the connection is pinned to the checked IP (no DNS rebinding),
 * ports 80/443 only, no redirects, a 5 MB cap enforced while downloading, and the
 * bytes must decode as a raster image (svg is refused: it can carry script).
 */
final class ResolveImportImage
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** getimagesize() mime => stored extension. */
    private const MIME_EXT = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
    ];

    /** @var \Closure(string):array<int,string> host => resolved IPs */
    private \Closure $resolve;

    public function __construct(?\Closure $resolve = null)
    {
        $this->resolve = $resolve ?? fn (string $host) => self::dnsLookup($host);
    }

    public static function isEmbedded(string $ref): bool
    {
        return str_starts_with($ref, 'public:');
    }

    /** @throws \RuntimeException with an Arabic message when the image can't be used. */
    public function execute(string $ref, int $bankId): string
    {
        if (self::isEmbedded($ref)) {
            $path = substr($ref, 7);
            // Only files the parser itself stored for this bank (random hex name).
            if (! preg_match('#^bank-questions/'.$bankId.'/imports/[0-9a-f]{20}\.(jpe?g|png|gif|webp)$#', $path)) {
                throw new \RuntimeException('مرجع صورة مدمجة غير صالح.');
            }

            return $path;
        }

        [$host, $port, $ip] = $this->checkedTarget($ref);

        try {
            $curl = [
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                // abort mid-transfer once over the cap (no Content-Length needed)
                CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => fn ($ch, $total, $done) => $done > self::MAX_BYTES ? 1 : 0,
            ];
            if ($host !== $ip) {
                // connect to the address we just checked, not a fresh lookup
                $curl[CURLOPT_RESOLVE] = ["{$host}:{$port}:".(str_contains($ip, ':') ? "[{$ip}]" : $ip)];
            }
            $response = Http::timeout(15)->connectTimeout(5)->withoutRedirecting()->withOptions(['curl' => $curl])->get($ref);
        } catch (\Throwable $e) {
            throw new \RuntimeException("تعذر تحميل الصورة (حتى 5 ميجابايت): {$ref}");
        }

        $body = $response->body();
        $info = $body !== '' && strlen($body) <= self::MAX_BYTES ? @getimagesizefromstring($body) : false;
        $ext = $info ? (self::MIME_EXT[$info['mime'] ?? ''] ?? null) : null;
        if (! $response->successful() || $ext === null) {
            throw new \RuntimeException("تعذر تحميل الصورة أو أنها ليست صورة صالحة (JPG/PNG/GIF/WEBP حتى 5 ميجابايت): {$ref}");
        }

        $path = "bank-questions/{$bankId}/".bin2hex(random_bytes(10)).'.'.$ext;
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /** @return array{0:string,1:int,2:string} host, port, the public IP to connect to */
    private function checkedTarget(string $ref): array
    {
        $parts = parse_url($ref);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user'])
            || ! in_array($port, [80, 443], true)) {
            throw new \RuntimeException("رابط الصورة غير صالح: {$ref}");
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolve)($host);
        if ($ips === [] || array_filter($ips, fn ($ip) => ! self::isPublicIp($ip)) !== []) {
            throw new \RuntimeException("رابط الصورة غير مسموح به: {$ref}");
        }

        return [$host, $port, $ips[0]];
    }

    public static function isPublicIp(string $ip): bool
    {
        $flags = defined('FILTER_FLAG_GLOBAL_RANGE')
            ? FILTER_FLAG_GLOBAL_RANGE
            : FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }
        // NAT64 (64:ff9b::/96) can embed any IPv4, private ones included.
        $bin = @inet_pton($ip);

        return ! ($bin !== false && strlen($bin) === 16 && str_starts_with($bin, "\x00\x64\xff\x9b".str_repeat("\x00", 8)));
    }

    /** @return array<int,string> every A and AAAA record (hosts file honoured for A). */
    private static function dnsLookup(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $rec) {
            if (isset($rec['ipv6'])) {
                $ips[] = $rec['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
