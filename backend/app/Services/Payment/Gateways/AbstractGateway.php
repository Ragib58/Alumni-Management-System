<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Data\InitiateResult;
use App\Services\Payment\Data\VerificationResult;

/**
 * Shared behaviour for gateway adapters, including the local sandbox simulator
 * used when PAYMENT_MODE=sandbox so the whole flow is testable without live keys.
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return (array) config('payment.gateways.'.$this->key(), []);
    }

    protected function inSandboxMode(): bool
    {
        return config('payment.mode', 'sandbox') === 'sandbox';
    }

    /**
     * A tamper-proof token binding a browser redirect back to this payment.
     */
    public function signToken(Payment $payment): string
    {
        return hash_hmac(
            'sha256',
            $payment->id.'|'.$payment->transaction_id.'|'.$this->key(),
            (string) config('app.key')
        );
    }

    protected function tokenIsValid(Payment $payment, ?string $token): bool
    {
        return is_string($token) && hash_equals($this->signToken($payment), $token);
    }

    /**
     * Build the redirect to the SPA's simulated-gateway page.
     */
    protected function sandboxRedirect(Payment $payment): InitiateResult
    {
        $url = rtrim((string) config('payment.frontend_sandbox_url'), '/')
            .'?'.http_build_query([
                'payment'     => $payment->id,
                'gateway'     => $this->key(),
                'token'       => $this->signToken($payment),
                'amount'      => $payment->amount,
                'transaction' => $payment->transaction_id,
            ]);

        return InitiateResult::redirect($url, sandbox: true, raw: ['mode' => 'sandbox']);
    }

    /**
     * Sandbox verification: trust the signed token + the simulated outcome.
     *
     * @param array<string, mixed> $callbackData
     */
    protected function sandboxVerify(Payment $payment, array $callbackData): VerificationResult
    {
        if (! $this->tokenIsValid($payment, $callbackData['token'] ?? null)) {
            return VerificationResult::failed('Invalid or tampered payment token.');
        }

        $outcome = $callbackData['outcome'] ?? 'success';

        if ($outcome === 'success') {
            return VerificationResult::paid(
                gatewayTransactionId: strtoupper($this->key()).'-'.strtoupper(bin2hex(random_bytes(5))),
                amount: (float) $payment->amount,
                raw: ['mode' => 'sandbox', 'outcome' => 'success'],
            );
        }

        return VerificationResult::failed('Payment was cancelled or failed in sandbox.', ['outcome' => $outcome]);
    }
}
