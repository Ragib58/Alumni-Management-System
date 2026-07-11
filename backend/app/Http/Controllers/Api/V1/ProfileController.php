<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateProfileRequest;
use App\Http\Resources\AlumniProfileResource;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated alumni member's own profile ("My Profile" / "Edit Profile").
 */
class ProfileController extends Controller
{
    public function __construct(private readonly AlumniService $alumni)
    {
    }

    /**
     * GET /api/v1/profile
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->alumni->findByUserId($request->user()->id);

        if (! $profile) {
            return $this->success(null, 'No profile found yet.');
        }

        return $this->success(new AlumniProfileResource($profile), 'Your profile.');
    }

    /**
     * PUT /api/v1/profile — update the current user's own profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->alumni->updateForUser(
            $request->user()->id,
            $request->safe()->except('profile_photo'),
            $request->file('profile_photo')
        );

        return $this->success(new AlumniProfileResource($profile), 'Profile updated successfully.');
    }
}
