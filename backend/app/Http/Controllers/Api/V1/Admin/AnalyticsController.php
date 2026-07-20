<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    /**
     * GET /api/v1/admin/analytics/dashboard
     * Cards + all charts in one payload.
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        return $this->success([
            'cards'  => $this->analytics->cards(),
            'charts' => $this->analytics->charts(),
        ], 'Analytics dashboard retrieved.');
    }

    /**
     * GET /api/v1/admin/analytics/year-comparison?year_a=2025&year_b=2026
     */
    public function yearComparison(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $current = (int) Carbon::now()->year;
        $yearA = (int) $request->integer('year_a', $current - 1);
        $yearB = (int) $request->integer('year_b', $current);

        return $this->success([
            'available_years' => $this->analytics->availableYears(),
            'comparison'      => $this->analytics->yearComparison($yearA, $yearB),
        ], 'Year comparison retrieved.');
    }
}
