<?php

namespace App\Repositories\Eloquent;

use App\Models\Sponsor;
use App\Repositories\Contracts\SponsorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SponsorRepository extends BaseRepository implements SponsorRepositoryInterface
{
    public function __construct(Sponsor $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with('event:id,title')
            ->forEvent($filters['event_id'] ?? null)
            ->when($filters['sponsor_type'] ?? null, fn ($q, $t) => $q->where('sponsor_type', $t))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->where('name', 'ILIKE', $like);
            })
            ->ranked()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeForEvent(int $eventId): Collection
    {
        return $this->query()->active()->forEvent($eventId)->ranked()->get();
    }
}
