<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    /**
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status', 'role', 'sort_by', 'sort_dir']);
        $perPage = (int) $request->integer('per_page', 15);

        $paginator = $this->users->paginate($filters, $perPage);

        return $this->success(
            UserResource::collection($paginator),
            'Users retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return $this->success(new UserResource($user), 'User created successfully.', 201);
    }

    /**
     * GET /api/v1/users/{user}
     */
    public function show(int $user): JsonResponse
    {
        $model = $this->users->find($user);
        $this->authorize('view', $model);

        return $this->success(new UserResource($model), 'User retrieved successfully.');
    }

    /**
     * PUT/PATCH /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $model = $this->users->find($user);
        $this->authorize('update', $model);

        $updated = $this->users->update($user, $request->validated());

        return $this->success(new UserResource($updated), 'User updated successfully.');
    }

    /**
     * PATCH /api/v1/users/{user}/status
     */
    public function updateStatus(UpdateUserStatusRequest $request, int $user): JsonResponse
    {
        $model = $this->users->find($user);
        $this->authorize('updateStatus', $model);

        $updated = $this->users->updateStatus($user, $request->validated()['status']);

        return $this->success(new UserResource($updated), 'User status updated successfully.');
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(int $user): JsonResponse
    {
        $model = $this->users->find($user);
        $this->authorize('delete', $model);

        $this->users->delete($user);

        return $this->success(null, 'User deleted successfully.');
    }
}
