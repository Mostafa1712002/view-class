<?php

namespace App\Modules\Auth\Services;

use InvalidArgumentException;
use RuntimeException;

final class JwtService
{
    public function __construct(private string $secret) {}

    public static function create(): self
    {
        $secret = config('auth.jwt_secret') ?: config('app.key');
        if (! $secret) {
            throw new RuntimeException('JWT secret (APP_KEY) is not configured.');
        }

        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        return new self($secret);
    }

    /** @param array<string, mixed> $claims */
    public function encode(array $claims, int $ttlSeconds): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->sign($encodedHeader . '.' . $encodedPayload);

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    /** @return array<string, mixed> */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Malformed token.');
        }

        [$header, $payload, $signature] = $parts;

        if (! hash_equals($this->sign($header . '.' . $payload), $signature)) {
            throw new InvalidArgumentException('Invalid token signature.');
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (! is_array($claims)) {
            throw new InvalidArgumentException('Invalid token payload.');
        }

        if (isset($claims['exp']) && time() >= $claims['exp']) {
            throw new InvalidArgumentException('Token expired.');
        }

        return $claims;
    }

    private function sign(string $input): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $input, $this->secret, true));
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $input): string
    {
        $padded = str_pad($input, strlen($input) + ((4 - strlen($input) % 4) % 4), '=', STR_PAD_RIGHT);
        return base64_decode(strtr($padded, '-_', '+/'));
    }
}
