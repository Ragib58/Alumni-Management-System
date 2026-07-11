<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginateWithFilters($filters, $perPage);
    }

    public function find(int $id): User
    {
        /** @var User $user */
        $user = $this->users->findOrFail($id);

        return $user->load(['roles:id,name', 'alumniProfile']);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            /** @var User $user */
            $user = $this->users->create(Arr::only($data, [
                'name', 'email', 'phone', 'password', 'status',
            ]));

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user->load(['roles:id,name', 'alumniProfile']);
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var User $user */
            $user = $this->users->findOrFail($id);

            $payload = Arr::only($data, ['name', 'email', 'phone', 'status']);

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $this->users->update($user, $payload);

            if (array_key_exists('roles', $data) && $data['roles'] !== null) {
                $user->syncRoles($data['roles']);
            }

            return $user->load(['roles:id,name', 'alumniProfile']);
        });
    }

    public function updateStatus(int $id, string $status): User
    {
        /** @var User $user */
        $user = $this->users->findOrFail($id);
        $this->users->update($user, ['status' => $status]);

        return $user->load(['roles:id,name', 'alumniProfile']);
    }

    public function delete(int $id): bool
    {
        /** @var User $user */
        $user = $this->users->findOrFail($id);

        return $this->users->delete($user);
    }
}
