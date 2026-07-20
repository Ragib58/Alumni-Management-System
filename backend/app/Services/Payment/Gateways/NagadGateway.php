<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Data\InitiateResult;
use App\Services\Payment\Data\VerificationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nagad Merchant checkout adapter.
 *
 * The live flow uses an RSA-signed initialize → complete handshake. Signing is
 * intentionally isolated in signPayload()/decrypt() so credentials can be wired
 * without touching the flow. Sandbox mode routes through the local simulator.
 */
class NagadGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'nagad';
    }

    public function initiate(Payment $payment): InitiateResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxRedirect($payment);
        }

        $cfg = $this->config();
        $orderId = $payment->transaction_id;

        // Step 1: initialize.
        $initialize = Http::withHeaders($this->headers())->post(
            $cfg['base_url']."/api/dfs/check-out/initialize/{$cfg['merchant_id']}/{$orderId}",
            [
                'dateTime'      => now()->format('YmdHis'),
                'sensitiveData' => $this->signPayload([
                    'merchantId' => $cfg['merchant_id'],
                    'orderId'    => $orderId,
                    'amount'     => (string) $payment->amount,
                    'currencyCode' => '050',
                ]),
                'signature' => $this->makeSignature($orderId),
            ]
        );

        $body = $initialize->json() ?? [];

        if (! empty($body['callBackUrl'])) {
            return InitiateResult::redirect($body['callBackUrl'], raw: $body);
        }

        Log::warning('Nagad initialize failed', ['order_id' => $orderId, 'body' => $body]);

        return InitiateResult::failed($body['message'] ?? 'Unable to start Nagad payment.', $body);
    }

    public function verify(Payment $payment, array $callbackData): VerificationResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxVerify($payment, $callbackData);
        }

        $cfg = $this->config();
        $paymentRefId = $callbackData['payment_ref_id'] ?? $callbackData['paymentRefId'] ?? null;

        if (! $paymentRefId) {
            return VerificationResult::failed('Missing Nagad payment reference.', $callbackData);
        }

        $verify = Http::withHeaders($this->headers())
            ->get($cfg['base_url']."/api/dfs/verify/payment/{$paymentRefId}");

        $body = $verify->json() ?? [];
        $status = $body['status'] ?? null;
        $amountMatches = isset($body['amount']) && abs((float) $body['amount'] - (float) $payment->amount) < 0.01;

        if ($status === 'Success' && $amountMatches) {
            return VerificationResult::paid($body['issuerPaymentRefNo'] ?? $paymentRefId, (float) $body['amount'], $body);
        }

        return VerificationResult::failed('Nagad verification failed.', $body);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Content-Type'    => 'application/json',
            'X-KM-Api-Version' => 'v-0.2.0',
        ];
    }

    /**
     * RSA-encrypt the sensitive payload with Nagad's public key.
     * Returns base64 ciphertext (no-op placeholder until keys are configured).
     */
    private function signPayload(array $data): string
    {
        $publicKey = $this->config()['public_key'] ?? null;
        $json = json_encode($data);

        if ($publicKey && openssl_public_encrypt($json, $encrypted, $this->pem($publicKey, 'PUBLIC'))) {
            return base64_encode($encrypted);
        }

        return base64_encode((string) $json);
    }

    private function makeSignature(string $orderId): string
    {
        $privateKey = $this->config()['private_key'] ?? null;

        if ($privateKey && openssl_sign($orderId, $signature, $this->pem($privateKey, 'PRIVATE'), OPENSSL_ALGO_SHA256)) {
            return base64_encode($signature);
        }

        return '';
    }

    private function pem(string $key, string $type): string
    {
        return "-----BEGIN {$type} KEY-----\n".chunk_split($key, 64, "\n")."-----END {$type} KEY-----\n";
    }
}
