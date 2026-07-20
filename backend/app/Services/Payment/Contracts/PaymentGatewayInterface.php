<?php

namespace App\Services\Payment\Contracts;

use App\Models\Payment;
use App\Services\Payment\Data\InitiateResult;
use App\Services\Payment\Data\VerificationResult;

/**
 * Contract every payment gateway adapter must implement. This is the seam that
 * lets the PaymentService stay gateway-agnostic (SSLCommerz, bKash, Nagad …).
 */
interface PaymentGatewayInterface
{
    /**
     * The gateway key, e.g. "sslcommerz".
     */
    public function key(): string;

    /**
     * Create a payment session and return where to send the customer.
     */
    public function initiate(Payment $payment): InitiateResult;

    /**
     * Verify a transaction using the data received on the gateway
     * callback / IPN (or, in sandbox mode, the simulated outcome).
     *
     * @param array<string, mixed> $callbackData
     */
    public function verify(Payment $payment, array $callbackData): VerificationResult;
}
