<?php

namespace App\Modules\RawaniHub\DTOs;

/** Typed outcome of one contract call (design §4.2). Never carries the request body. */
final class PlatformResult
{
    public const OK = 'ok';
    public const CONFLICT = 'conflict';
    public const INVALID = 'invalid';
    public const RETRYABLE = 'retryable';
    public const UNSUPPORTED = 'unsupported';
    public const AUTH = 'auth';
    public const NOT_FOUND = 'not_found';

    public function __construct(
        public readonly string $status,
        public readonly int $httpStatus = 0,
        public readonly array $data = [],
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly array $details = [],
    ) {}

    public function ok(): bool
    {
        return $this->status === self::OK;
    }

    public function isRetryable(): bool
    {
        return $this->status === self::RETRYABLE;
    }
}
