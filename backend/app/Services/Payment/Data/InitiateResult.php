<?php

namespace App\Services\Payment\Data;

/**
 * Value object returned by a gateway when a payment session is created.
 */
final class InitiateResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $redirectUrl,
        /** Whether this is the local sandbox simulator rather than a live gateway. */
        public readonly bool $sandbox = false,
        /** Raw gateway response for auditing. */
        public readonly array $raw = [],
        public readonly ?string $message = null,
    ) {
    }

    public static function redirect(string $url, bool $sandbox = false, array $raw = []): self
    {
        return new self(true, $url, $sandbox, $raw);
    }

    public static function failed(string $message, array $raw = []): self
    {
        return new self(false, '', false, $raw, $message);
    }
}
