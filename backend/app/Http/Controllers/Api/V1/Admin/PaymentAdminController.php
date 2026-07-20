<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * GET /api/v1/admin/payments — Payment List.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only(['search', 'status', 'gateway', 'event_id']);
        $perPage = (int) $request->integer('per_page', 15);

        return $this->success(
            PaymentResource::collection($this->payments->adminList($filters, $perPage)),
            'Payments retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/admin/payments/{payment} — Transaction Details.
     */
    public function show(int $payment): JsonResponse
    {
        $model = $this->payments->find($payment);
        $this->authorize('view', $model);

        return $this->success(new PaymentResource($model), 'Payment retrieved successfully.');
    }

    /**
     * GET /api/v1/admin/payments-revenue — Revenue Dashboard.
     */
    public function revenue(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        return $this->success($this->payments->revenue(), 'Revenue statistics retrieved.');
    }

    /**
     * POST /api/v1/admin/payments/{payment}/refund
     */
    public function refund(int $payment): JsonResponse
    {
        $model = $this->payments->find($payment);
        $this->authorize('refund', $model);

        $refunded = $this->payments->refund($payment);

        return $this->success(new PaymentResource($refunded->load('registration')), 'Payment refunded.');
    }
}
