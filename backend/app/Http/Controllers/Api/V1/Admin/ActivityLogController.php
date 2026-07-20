<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogRepositoryInterface $logs)
    {
    }

    /**
     * GET /api/v1/admin/activity-logs
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasAnyRole(['super_admin', 'event_manager']),
            403
        );

        $filters = $request->only(['action', 'user_id', 'search']);
        $perPage = (int) $request->integer('per_page', 25);

        return $this->success(
            ActivityLogResource::collection($this->logs->paginateWithFilters($filters, $perPage)),
            'Activity logs retrieved.',
            200,
            ['actions' => array_map(
                fn (ActivityAction $a) => ['value' => $a->value, 'label' => $a->label()],
                ActivityAction::cases()
            )]
        );
    }
}
