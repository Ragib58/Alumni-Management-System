<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SponsorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsor\StoreSponsorRequest;
use App\Http\Requests\Sponsor\UpdateSponsorRequest;
use App\Http\Resources\SponsorResource;
use App\Models\Sponsor;
use App\Services\SponsorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function __construct(private readonly SponsorService $sponsors)
    {
    }

    /**
     * GET /api/v1/admin/sponsors
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sponsor::class);

        $filters = $request->only(['search', 'event_id', 'sponsor_type']);
        $perPage = (int) $request->integer('per_page', 20);

        return $this->success(
            SponsorResource::collection($this->sponsors->paginate($filters, $perPage)),
            'Sponsors retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/admin/sponsors/meta
     */
    public function meta(): JsonResponse
    {
        return $this->success([
            'types' => array_map(fn (SponsorType $t) => ['value' => $t->value, 'label' => $t->label()], SponsorType::cases()),
        ], 'Sponsor metadata.');
    }

    public function show(int $sponsor): JsonResponse
    {
        $model = $this->sponsors->find($sponsor);
        $this->authorize('update', $model);

        return $this->success(new SponsorResource($model->load('event:id,title')), 'Sponsor retrieved.');
    }

    public function store(StoreSponsorRequest $request): JsonResponse
    {
        $sponsor = $this->sponsors->create($request->safe()->except('logo'), $request->file('logo'));

        return $this->success(new SponsorResource($sponsor), 'Sponsor created successfully.', 201);
    }

    public function update(UpdateSponsorRequest $request, int $sponsor): JsonResponse
    {
        $model = $this->sponsors->find($sponsor);
        $this->authorize('update', $model);

        $updated = $this->sponsors->update($sponsor, $request->safe()->except('logo'), $request->file('logo'));

        return $this->success(new SponsorResource($updated), 'Sponsor updated successfully.');
    }

    public function destroy(int $sponsor): JsonResponse
    {
        $model = $this->sponsors->find($sponsor);
        $this->authorize('delete', $model);

        $this->sponsors->delete($sponsor);

        return $this->success(null, 'Sponsor deleted successfully.');
    }
}
