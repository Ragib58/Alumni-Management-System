<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateProfileRequest;
use App\Http\Resources\AlumniProfileResource;
use App\Models\AlumniProfile;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function __construct(private readonly AlumniService $alumni)
    {
    }

    /**
     * GET /api/v1/alumni  — Alumni Directory (search + filters).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AlumniProfile::class);

        $filters = $request->only([
            'search', 'batch', 'department', 'session', 'profession', 'sort_by', 'sort_dir',
        ]);
        $perPage = (int) $request->integer('per_page', 12);

        $paginator = $this->alumni->directory($filters, $perPage);

        return $this->success(
            AlumniProfileResource::collection($paginator),
            'Alumni directory retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/alumni/filters — distinct option lists for the directory UI.
     */
    public function filters(): JsonResponse
    {
        return $this->success($this->alumni->filterOptions(), 'Filter options retrieved.');
    }

    /**
     * GET /api/v1/alumni/{alumni} — single profile by id.
     */
    public function show(int $alumni): JsonResponse
    {
        $profile = $this->alumni->find($alumni);
        $this->authorize('view', $profile);

        return $this->success(new AlumniProfileResource($profile), 'Alumni profile retrieved.');
    }

    /**
     * PUT /api/v1/alumni/{alumni} — admin/manager updates any profile.
     */
    public function update(UpdateProfileRequest $request, int $alumni): JsonResponse
    {
        $profile = $this->alumni->find($alumni);
        $this->authorize('update', $profile);

        $updated = $this->alumni->update(
            $alumni,
            $request->safe()->except('profile_photo'),
            $request->file('profile_photo')
        );

        return $this->success(new AlumniProfileResource($updated), 'Alumni profile updated.');
    }
}
