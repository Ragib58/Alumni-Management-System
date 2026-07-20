<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    /**
     * GET /api/v1/public/settings — safe subset for the SPA (site name, logo, theme).
     */
    public function publicSettings(): JsonResponse
    {
        return $this->success($this->settings->publicSettings(), 'Public settings retrieved.');
    }

    /**
     * GET /api/v1/admin/settings — all settings grouped (secrets masked).
     */
    public function index(): JsonResponse
    {
        $this->authorizeRole();

        return $this->success($this->settings->grouped(includeSecret: false), 'Settings retrieved.');
    }

    /**
     * PUT /api/v1/admin/settings — bulk update.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settings->bulkUpdate($request->validated()['settings']);

        return $this->success($this->settings->grouped(includeSecret: false), 'Settings updated successfully.');
    }

    private function authorizeRole(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(['super_admin']),
            403,
            'Only Super Admins can manage settings.'
        );
    }
}
