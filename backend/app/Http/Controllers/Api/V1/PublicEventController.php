<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unauthenticated, read-only access to published events (public event pages).
 */
class PublicEventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    /**
     * GET /api/v1/public/events
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type', 'upcoming', 'sort_by', 'sort_dir']);
        $filters['published_only'] = true;

        $perPage = (int) $request->integer('per_page', 12);

        return $this->success(
            EventResource::collection($this->events->paginate($filters, $perPage)),
            'Public events retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/public/events/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $event = $this->events->findBySlug($slug, withFields: true);
        $this->events->assertPublicallyVisible($event);

        return $this->success(new EventResource($event), 'Event retrieved successfully.');
    }
}
