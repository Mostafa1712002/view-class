<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

/** Business-rule refusal. `reason` is a stable key controllers map to a message. */
final class AttemptException extends \RuntimeException
{
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message ?: $reason);
    }
}
