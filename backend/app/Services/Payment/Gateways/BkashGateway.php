<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Data\InitiateResult;
use App\Services\Payment\Data\VerificationResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * bKash Tokenized Checkout adapter.
 *
 * Live flow: grant token → create payment → (customer completes on bKash) →
 * execute payment to confirm. In sandbox mode we short-circuit to the local
 * simulator so the end-to-end flow works without merchant credentials.
 */
class BkashGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'bkash';
    }

    public function initiate(Payment $payment): InitiateResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxRedirect($payment);
        }

        $cfg = $this->config();

        $token = $this->grantToken();
        if (! $token) {
            return InitiateResult::failed('Unable to authenticate with bKash.');
        }

        $response = Http::withHeaders([
            'Authorization' => $token,
            'X-App-Key'     => $cfg['app_key'],
        ])->post($cfg['base_url'].'/tokenized/checkout/create', [
            'mode'                  => '0011',
            'payerReference'        => $payment->registration?->registration_no ?? (string) $payment->id,
            'callbackURL'           => route('payments.return', ['gateway' => $this->key()]),
            'amount'                => (string) $payment->amount,
            'currency'              => $payment->currency,
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $payment->transaction_id,
        ]);

        $body = $response->json() ?? [];

        if (! empty($body['bkashURL'])) {
            // Persist the gateway's paymentID for the execute step during verify.
            $payment->forceFill(['gateway_transaction_id' => $body['paymentID'] ?? null])->save();

            return InitiateResult::redirect($body['bkashURL'], raw: $body);
        }

        Log::warning('bKash create payment failed', ['tran_id' => $payment->transaction_id, 'body' => $body]);

        return InitiateResult::failed($body['statusMessage'] ?? 'Unable to start bKash payment.', $body);
    }

    public function verify(Payment $payment, array $callbackData): VerificationResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxVerify($payment, $callbackData);
        }

        // bKash returns paymentID + status on the callback.
        $status = $callbackData['status'] ?? null;
        $paymentId = $callbackData['paymentID'] ?? $payment->gateway_transaction_id;

        if ($status && strtolower($status) !== 'success') {
            return VerificationResult::failed('bKash payment was not successful.', $callbackData);
        }

        $cfg = $this->config();
        $token = $this->grantToken();
        if (! $token || ! $paymentId) {
            return VerificationResult::failed('Unable to execute bKash payment.', $callbackData);
        }

        $response = Http::withHeaders([
            'Authorization' => $token,
            'X-App-Key'     => $cfg['app_key'],
        ])->post($cfg['base_url'].'/tokenized/checkout/execute', [
            'paymentID' => $paymentId,
        ]);

        $body = $response->json() ?? [];
        $txStatus = $body['transactionStatus'] ?? null;
        $amountMatches = isset($body['amount']) && abs((float) $body['amount'] - (float) $payment->amount) < 0.01;

        if ($txStatus === 'Completed' && $amountMatches) {
            return VerificationResult::paid($body['trxID'] ?? $paymentId, (float) $body['amount'], $body);
        }

        return VerificationResult::failed('bKash execution failed.', $body);
    }

    /**
     * Grant (and cache) a bKash id_token.
     */
    private function grantToken(): ?string
    {
        $cfg = $this->config();

        return Cache::remember('bkash_token', now()->addMinutes(50), function () use ($cfg) {
            $response = Http::withHeaders([
                'username' => $cfg['username'],
                'password' => $cfg['password'],
            ])->post($cfg['base_url'].'/tokenized/checkout/token/grant', [
                'app_key'    => $cfg['app_key'],
                'app_secret' => $cfg['app_secret'],
            ]);

            return $response->json('id_token');
        });
    }
}
