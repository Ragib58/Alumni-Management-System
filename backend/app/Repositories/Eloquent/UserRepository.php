<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->query()->where('email', $email)->first();

        return $user;
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['name', 'email', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        return $this->query()
            ->with(['roles:id,name', 'alumniProfile'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('name', 'ILIKE', $like)
                        ->orWhere('email', 'ILIKE', $like)
                        ->orWhere('phone', 'ILIKE', $like);
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['role'] ?? null, function ($q, $role) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', $role));
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countActive(): int
    {
        return $this->query()->where('status', UserStatus::Active->value)->count();
    }

    public function countByStatus(string $status): int
    {
        return $this->query()->where('status', $status)->count();
    }

    public function totalCount(): int
    {
        return $this->query()->count();
    }
}
