<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /**
     * GET /api/v1/dashboard/statistics
     * Restricted to Super Admin / Event Manager via route middleware.
     */
    public function statistics(): JsonResponse
    {
        return $this->success(
            $this->dashboard->statistics(),
            'Dashboard statistics retrieved successfully.'
        );
    }
}
