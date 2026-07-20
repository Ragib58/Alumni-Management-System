<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->query()
            ->with('user:id,name,email')
            ->action($filters['action'] ?? null)
            ->forUser($filters['user_id'] ?? null)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->where('description', 'ILIKE', $like)
                    ->orWhereHas('user', fn ($uq) => $uq
                        ->where('name', 'ILIKE', $like)
                        ->orWhere('email', 'ILIKE', $like));
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
