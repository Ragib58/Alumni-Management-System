<?php

namespace App\Services\Payment\Data;

/**
 * Value object describing the outcome of verifying a gateway transaction.
 */
final class VerificationResult
{
    public function __construct(
        public readonly bool $paid,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?float $amount = null,
        public readonly array $raw = [],
        public readonly ?string $message = null,
    ) {
    }

    public static function paid(?string $gatewayTransactionId, ?float $amount = null, array $raw = []): self
    {
        return new self(true, $gatewayTransactionId, $amount, $raw);
    }

    public static function failed(?string $message = null, array $raw = []): self
    {
        return new self(false, null, null, $raw, $message);
    }
}
