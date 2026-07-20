<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\FormFieldType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    /**
     * GET /api/v1/events — authenticated catalogue (published by default,
     * admins may pass status to see all).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Event::class);

        $isAdmin = $request->user()->hasAnyRole(['super_admin', 'event_manager']);

        $filters = $request->only(['search', 'type', 'status', 'upcoming', 'sort_by', 'sort_dir']);
        // Non-admins only ever see published events.
        $filters['published_only'] = $isAdmin ? $request->boolean('published_only') : true;

        $perPage = (int) $request->integer('per_page', 12);

        return $this->success(
            EventResource::collection($this->events->paginate($filters, $perPage)),
            'Events retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/events/meta — enum option lists for the builder/forms.
     */
    public function meta(): JsonResponse
    {
        return $this->success([
            'types'       => array_map(fn (EventType $t) => ['value' => $t->value, 'label' => $t->label()], EventType::cases()),
            'statuses'    => array_map(fn (EventStatus $s) => ['value' => $s->value, 'label' => $s->label()], EventStatus::cases()),
            'field_types' => array_map(fn (FormFieldType $f) => [
                'value'            => $f->value,
                'label'            => $f->label(),
                'requires_options' => $f->requiresOptions(),
            ], FormFieldType::cases()),
        ], 'Event metadata.');
    }

    /**
     * GET /api/v1/events/{id} — single event with form fields (admin view).
     */
    public function show(int $id): JsonResponse
    {
        $event = $this->events->findById($id);
        $this->authorize('view', $event);

        return $this->success(new EventResource($event), 'Event retrieved successfully.');
    }

    /**
     * GET /api/v1/events/slug/{slug} — single event with form fields for the
     * authenticated details & registration pages. Non-admins only see published.
     */
    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $event = $this->events->findBySlug($slug, withFields: true);
        $this->authorize('view', $event);

        $isAdmin = $request->user()->hasAnyRole(['super_admin', 'event_manager']);
        if (! $isAdmin) {
            $this->events->assertPublicallyVisible($event);
        }

        return $this->success(new EventResource($event), 'Event retrieved successfully.');
    }

    /**
     * POST /api/v1/events
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->events->create(
            $request->safe()->except('banner'),
            $request->file('banner'),
            $request->user()->id
        );

        return $this->success(new EventResource($event), 'Event created successfully.', 201);
    }

    /**
     * PUT/POST /api/v1/events/{id}
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = $this->events->findById($id);
        $this->authorize('update', $event);

        $updated = $this->events->update(
            $id,
            $request->safe()->except('banner'),
            $request->file('banner')
        );

        return $this->success(new EventResource($updated), 'Event updated successfully.');
    }

    /**
     * DELETE /api/v1/events/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $event = $this->events->findById($id);
        $this->authorize('delete', $event);

        $this->events->delete($id);

        return $this->success(null, 'Event deleted successfully.');
    }
}
