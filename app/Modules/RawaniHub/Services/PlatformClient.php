<?php

namespace App\Modules\RawaniHub\Services;

use App\Modules\RawaniHub\DTOs\PlatformResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The single HTTP client for the rawani/v1 contract (design §4).
 * No internal retries: the sync job owns retry/backoff and reads PlatformResult::isRetryable().
 */
class PlatformClient
{
    public function __construct(private string $baseUrl, private string $secret) {}

    public function ping(): PlatformResult
    {
        return $this->send('GET', '/ping');
    }

    public function putAccount(string $role, int|string $rawaniId, array $state): PlatformResult
    {
        return $this->send('PUT', "/accounts/{$role}/{$rawaniId}", [], $state);
    }

    public function sso(string $role, int|string $rawaniId): PlatformResult
    {
        return $this->send('POST', "/accounts/{$role}/{$rawaniId}/sso", [], []);
    }

    public function people(?string $cursor = null, int $per = 500): PlatformResult
    {
        return $this->send('GET', '/people', ['cursor' => $cursor, 'per' => $per]);
    }

    /** Query per §4.1: drop empty values, ksort, RFC 3986. Returns '' or '?a=1&b=2'. */
    public static function queryString(array $query): string
    {
        $query = array_filter($query, fn ($v) => $v !== null && $v !== '');
        ksort($query);

        return $query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '';
    }

    /** $pathWithQuery is the logical path plus the query string, exactly as sent. */
    public static function sign(string $secret, string $method, string $pathWithQuery, int $timestamp, string $nonce, string $body): string
    {
        $canonical = strtoupper($method)."\n".$pathWithQuery."\n".$timestamp."\n".$nonce."\n".hash('sha256', $body);

        return hash_hmac('sha256', $canonical, $secret);
    }

    private function send(string $method, string $path, array $query = [], ?array $payload = null): PlatformResult
    {
        $pathWithQuery = $path.self::queryString($query);
        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ts = time();
        $nonce = (string) Str::uuid();

        try {
            $request = Http::timeout(15)
                ->withHeaders([
                    'X-Rawani-Timestamp' => (string) $ts,
                    'X-Rawani-Nonce' => $nonce,
                    'X-Rawani-Signature' => self::sign($this->secret, $method, $pathWithQuery, $ts, $nonce, $body),
                ])
                ->acceptJson();

            $url = rtrim($this->baseUrl, '/').$pathWithQuery;
            $response = $payload === null
                ? $request->send($method, $url)
                : $request->withBody($body, 'application/json')->send($method, $url);
        } catch (ConnectionException) {
            return new PlatformResult(PlatformResult::RETRYABLE, 0, code: 'PLATFORM_ERROR', message: 'connection failed');
        }

        return $this->map($response);
    }

    private function map(Response $r): PlatformResult
    {
        $http = $r->status();
        $json = $r->json();
        $json = is_array($json) ? $json : [];

        if ($r->successful() && ($json['success'] ?? false) === true) {
            return new PlatformResult(PlatformResult::OK, $http, (array) ($json['data'] ?? []));
        }

        $code = $json['error']['code'] ?? null;
        $message = $json['error']['message'] ?? null;
        $details = (array) ($json['error']['details'] ?? []);

        $status = match (true) {
            $http === 401 => PlatformResult::AUTH,
            $http === 404 => PlatformResult::NOT_FOUND,
            $http === 409 => PlatformResult::CONFLICT,
            $http === 501 => PlatformResult::UNSUPPORTED,
            $http === 429, $http >= 500 => PlatformResult::RETRYABLE,
            default => PlatformResult::INVALID, // 422 and any other 4xx, or a 2xx without the envelope
        };
        if ($r->successful()) {
            $code ??= 'BAD_RESPONSE';
        }

        return new PlatformResult($status, $http, [], $code, $message, $details);
    }
}
