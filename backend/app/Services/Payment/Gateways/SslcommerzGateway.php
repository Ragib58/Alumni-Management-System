<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Data\InitiateResult;
use App\Services\Payment\Data\VerificationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslcommerzGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function initiate(Payment $payment): InitiateResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxRedirect($payment);
        }

        $cfg = $this->config();
        $registration = $payment->registration;
        $user = $registration?->user;

        // SSLCommerz "Create Session" API.
        $response = Http::asForm()->post($cfg['base_url'].'/gwprocess/v4/api.php', [
            'store_id'       => $cfg['store_id'],
            'store_passwd'   => $cfg['store_password'],
            'total_amount'   => $payment->amount,
            'currency'       => $payment->currency,
            'tran_id'        => $payment->transaction_id,
            'success_url'    => route('payments.return', ['gateway' => $this->key()]),
            'fail_url'       => route('payments.return', ['gateway' => $this->key()]),
            'cancel_url'     => route('payments.return', ['gateway' => $this->key()]),
            'ipn_url'        => route('payments.ipn', ['gateway' => $this->key()]),
            'cus_name'       => $user?->name ?? 'Guest',
            'cus_email'      => $user?->email ?? 'guest@ams.test',
            'cus_phone'      => $user?->phone ?? '01700000000',
            'product_name'   => $registration?->event?->title ?? 'Event Registration',
            'product_category' => 'event',
            'product_profile' => 'general',
            'shipping_method' => 'NO',
        ]);

        $body = $response->json() ?? [];

        if (($body['status'] ?? null) === 'SUCCESS' && ! empty($body['GatewayPageURL'])) {
            return InitiateResult::redirect($body['GatewayPageURL'], raw: $body);
        }

        Log::warning('SSLCommerz session failed', ['tran_id' => $payment->transaction_id, 'body' => $body]);

        return InitiateResult::failed($body['failedreason'] ?? 'Unable to start SSLCommerz payment.', $body);
    }

    public function verify(Payment $payment, array $callbackData): VerificationResult
    {
        if ($this->inSandboxMode()) {
            return $this->sandboxVerify($payment, $callbackData);
        }

        $cfg = $this->config();
        $valId = $callbackData['val_id'] ?? null;

        if (! $valId) {
            return VerificationResult::failed('Missing val_id from SSLCommerz.', $callbackData);
        }

        // Server-to-server validation (authoritative source of truth).
        $response = Http::get($cfg['base_url'].'/validator/api/validationserverAPI.php', [
            'val_id'       => $valId,
            'store_id'     => $cfg['store_id'],
            'store_passwd' => $cfg['store_password'],
            'format'       => 'json',
        ]);

        $body = $response->json() ?? [];
        $status = $body['status'] ?? null;

        $amountMatches = isset($body['amount']) && abs((float) $body['amount'] - (float) $payment->amount) < 0.01;

        if (in_array($status, ['VALID', 'VALIDATED'], true) && $amountMatches) {
            return VerificationResult::paid($valId, (float) $body['amount'], $body);
        }

        return VerificationResult::failed('SSLCommerz validation failed.', $body);
    }
}
