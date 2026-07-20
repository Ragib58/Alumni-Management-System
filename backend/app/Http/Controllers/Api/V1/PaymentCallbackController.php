<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Unauthenticated gateway callbacks (live mode). Gateways redirect the browser
 * here (return) or call server-to-server (IPN). We verify then bounce the user
 * back to the SPA success/failure page.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * GET|POST /api/v1/public/payments/{gateway}/return
     */
    public function return(Request $request, string $gateway): RedirectResponse
    {
        $data = $request->all();

        try {
            $payment = $this->payments->handleGatewayCallback($gateway, $data);
            $target = $payment->isPaid()
                ? config('payment.frontend_return_url')
                : config('payment.frontend_cancel_url');

            return redirect()->away($target.'?transaction='.$payment->transaction_id);
        } catch (\Throwable $e) {
            Log::error('Payment return handling failed', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return redirect()->away(config('payment.frontend_cancel_url').'?error=verification');
        }
    }

    /**
     * POST /api/v1/public/payments/{gateway}/ipn
     * Server-to-server notification. Returns JSON (no redirect).
     */
    public function ipn(Request $request, string $gateway): JsonResponse
    {
        try {
            $payment = $this->payments->handleGatewayCallback($gateway, $request->all());

            return response()->json(['success' => true, 'status' => $payment->status->value]);
        } catch (\Throwable $e) {
            Log::error('Payment IPN handling failed', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['success' => false], 200); // Ack to stop retries storm; logged for review.
        }
    }
}
