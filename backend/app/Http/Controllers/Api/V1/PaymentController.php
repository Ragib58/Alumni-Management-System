<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Requests\Payment\SandboxCompleteRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly RegistrationService $registrations,
    ) {
    }

    /**
     * POST /api/v1/registrations/{registration}/pay
     * Step 2 — create a gateway session and return where to redirect.
     */
    public function initiate(InitiatePaymentRequest $request, int $registration): JsonResponse
    {
        $model = $this->registrations->find($registration);

        $result = $this->payments->initiate($model, $request->validated()['gateway'], $request->user());

        return $this->success([
            'payment'      => new PaymentResource($result['payment']),
            'redirect_url' => $result['redirect_url'],
            'sandbox'      => $result['sandbox'],
        ], 'Payment initiated. Redirecting to gateway.', 201);
    }

    /**
     * POST /api/v1/payments/{payment}/sandbox-complete
     * Step 3 — the simulated gateway page reports the outcome (sandbox mode).
     */
    public function sandboxComplete(SandboxCompleteRequest $request, int $payment): JsonResponse
    {
        $updated = $this->payments->completeSandbox(
            $payment,
            $request->validated(),
            $request->user()
        );

        return $this->success(
            new PaymentResource($updated->load('registration')),
            $updated->isPaid() ? 'Payment successful.' : 'Payment failed.'
        );
    }

    /**
     * GET /api/v1/payments/{payment} — the payer polls their payment status.
     */
    public function show(int $payment): JsonResponse
    {
        $model = $this->payments->find($payment);
        $this->authorize('view', $model);

        return $this->success(new PaymentResource($model), 'Payment retrieved.');
    }

    /**
     * GET /api/v1/my-payments — the current user's payment history.
     */
    public function myPayments(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = (int) $request->integer('per_page', 10);

        return $this->success(
            PaymentResource::collection($this->payments->userList($request->user()->id, $filters, $perPage)),
            'Your payments retrieved successfully.'
        );
    }
}
