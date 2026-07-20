<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ExportService $exporter,
    ) {
    }

    /**
     * GET /api/v1/admin/reports/event
     */
    public function event(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only(['event_id']);

        return $this->success($this->reports->eventReport($filters), 'Event report retrieved.');
    }

    /**
     * GET /api/v1/admin/reports/financial
     */
    public function financial(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only(['status', 'gateway', 'event_id']);

        return $this->success($this->reports->financialReport($filters), 'Financial report retrieved.');
    }

    /**
     * GET /api/v1/admin/reports/alumni
     */
    public function alumni(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        return $this->success($this->reports->alumniReport(), 'Alumni report retrieved.');
    }

    /**
     * GET /api/v1/admin/reports/{type}/export/{format}
     * type: event|financial|alumni   format: excel|csv|pdf
     */
    public function export(Request $request, string $type, string $format): Response
    {
        $this->authorize('viewAny', Payment::class);

        $request->merge(compact('type', 'format'));
        $request->validate([
            'type'   => [Rule::in(['event', 'financial', 'alumni'])],
            'format' => [Rule::in(['excel', 'csv', 'pdf'])],
        ]);

        $filters = $request->only(['event_id', 'status', 'gateway']);

        return $this->exporter->export($type, $format, $filters);
    }
}
