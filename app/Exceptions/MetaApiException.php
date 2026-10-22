<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Meta's Graph API refused a call. An expired or revoked token (HTTP 401 / code 190) is told apart
 * from a temporary failure because only the first one needs somebody to reconnect the account.
 */
class MetaApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly ?int $metaCode = null)
    {
        parent::__construct($message);
    }

    public function isAuthError(): bool
    {
        return $this->status === 401 || $this->metaCode === 190;
    }
}
