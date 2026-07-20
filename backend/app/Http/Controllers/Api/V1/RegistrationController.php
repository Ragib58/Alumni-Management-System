<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\RegisterEventRequest;
use App\Http\Requests\Event\UpdateRegistrationStatusRequest;
use App\Http\Resources\EventRegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\EventService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly EventService $events,
    ) {
    }

    /**
     * POST /api/v1/events/{event}/register — authenticated user registers.
     * Files for file-type fields arrive as files[<field_name>].
     */
    public function register(RegisterEventRequest $request, int $event): JsonResponse
    {
        $model = $this->events->findById($event);

        $files = $request->file('files', []);
        $files = is_array($files) ? $files : [];

        $registration = $this->registrations->register(
            $request->user(),
            $model,
            $request->validated()['form_response'] ?? [],
            $files
        );

        return $this->success(
            new EventRegistrationResource($registration),
            'Registration successful. Payment is pending.',
            201
        );
    }

    /**
     * GET /api/v1/my-registrations — the current user's registrations.
     */
    public function myRegistrations(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = (int) $request->integer('per_page', 10);

        return $this->success(
            EventRegistrationResource::collection(
                $this->registrations->userList($request->user()->id, $filters, $perPage)
            ),
            'Your registrations retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/my-registrations/{registration} — a single registration the
     * current user owns (used by the payment page).
     */
    public function showOwn(int $registration): JsonResponse
    {
        $model = $this->registrations->find($registration);
        $this->authorize('view', $model); // policy allows owner or manager

        return $this->success(new EventRegistrationResource($model), 'Registration retrieved.');
    }

    /**
     * DELETE /api/v1/registrations/{registration}/cancel — cancel own registration.
     */
    public function cancel(Request $request, int $registration): JsonResponse
    {
        $model = $this->registrations->find($registration);
        $this->authorize('cancel', $model);

        $updated = $this->registrations->cancelOwn($request->user(), $registration);

        return $this->success(new EventRegistrationResource($updated), 'Registration cancelled.');
    }

    /* ------------------------------- Admin -------------------------------- */

    /**
     * GET /api/v1/registrations — admin list across all events (filterable).
     * GET /api/v1/events/{event}/registrations — scoped to one event.
     */
    public function index(Request $request, ?int $event = null): JsonResponse
    {
        $this->authorize('viewAny', EventRegistration::class);

        $filters = $request->only(['status', 'search']);
        if ($event) {
            // Ensure the event exists (404 otherwise) and scope the query.
            $this->events->findById($event);
            $filters['event_id'] = $event;
        } elseif ($request->filled('event_id')) {
            $filters['event_id'] = (int) $request->input('event_id');
        }

        $perPage = (int) $request->integer('per_page', 15);

        return $this->success(
            EventRegistrationResource::collection($this->registrations->adminList($filters, $perPage)),
            'Registrations retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/registrations/{registration}
     */
    public function show(int $registration): JsonResponse
    {
        $model = $this->registrations->find($registration);
        $this->authorize('view', $model);

        return $this->success(new EventRegistrationResource($model), 'Registration retrieved.');
    }

    /**
     * PATCH /api/v1/registrations/{registration}/status
     */
    public function updateStatus(UpdateRegistrationStatusRequest $request, int $registration): JsonResponse
    {
        $model = $this->registrations->find($registration);
        $this->authorize('updateStatus', $model);

        $data = $request->validated();
        $updated = $this->registrations->updateStatus(
            $registration,
            $data['status'],
            $data['payment_status'] ?? null
        );

        return $this->success(new EventRegistrationResource($updated), 'Registration status updated.');
    }
}
