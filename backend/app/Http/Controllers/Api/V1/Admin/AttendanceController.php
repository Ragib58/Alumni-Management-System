<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AnalyticsService;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly CheckInService $checkIn,
        private readonly AnalyticsService $analytics,
    ) {
    }

    /**
     * POST /api/v1/admin/attendance/check-in
     * Scan a QR (or pass registration_id) to mark attendance.
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $this->authorize('manage', Attendance::class);

        $data = $request->validated();
        $eventId = $data['event_id'] ?? null;

        $result = ! empty($data['qr'])
            ? $this->checkIn->checkInByQr($data['qr'], $request->user(), $eventId)
            : $this->checkIn->checkInByRegistration((int) $data['registration_id'], $request->user(), $eventId);

        $this->analytics->flush();

        return $this->success(
            new AttendanceResource($result['attendance']),
            $result['duplicate'] ? 'Already checked in.' : 'Checked in successfully.',
            $result['duplicate'] ? 200 : 201
        );
    }

    /**
     * POST /api/v1/admin/attendance/check-out
     */
    public function checkOut(Request $request): JsonResponse
    {
        $this->authorize('manage', Attendance::class);

        $registrationId = (int) $request->input('registration_id');
        $attendance = $this->checkIn->checkOut($registrationId, $request->user());

        $this->analytics->flush();

        return $this->success(new AttendanceResource($attendance), 'Checked out successfully.');
    }

    /**
     * GET /api/v1/admin/events/{event}/attendance
     */
    public function index(Request $request, int $event): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->only(['status', 'search']);
        $perPage = (int) $request->integer('per_page', 20);

        return $this->success(
            AttendanceResource::collection($this->checkIn->listForEvent($event, $filters, $perPage)),
            'Attendance list retrieved.',
            200,
            ['stats' => $this->checkIn->statsForEvent($event)]
        );
    }

    /**
     * GET /api/v1/admin/events/{event}/attendance/stats
     */
    public function stats(int $event): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        return $this->success($this->checkIn->statsForEvent($event), 'Attendance stats retrieved.');
    }
}
