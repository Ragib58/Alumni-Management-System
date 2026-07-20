<?php

namespace App\Repositories\Eloquent;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    /**
     * Eager-load the active-registration count so capacity attributes resolve
     * without extra queries.
     */
    private function withSeatCount()
    {
        return $this->query()->withCount([
            'registrations as active_registrations_count' => fn ($q) => $q->active(),
        ]);
    }

    public function paginateWithFilters(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $sortBy  = in_array(($filters['sort_by'] ?? null), ['event_date', 'created_at', 'title'], true)
            ? $filters['sort_by']
            : 'event_date';
        $sortDir = strtolower($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $this->withSeatCount()
            ->with('creator:id,name')
            ->when($filters['published_only'] ?? false, fn ($q) => $q->published())
            ->when($filters['upcoming'] ?? false, fn ($q) => $q->where('event_date', '>=', Carbon::now()->startOfDay()))
            ->search($filters['search'] ?? null)
            ->type($filters['type'] ?? null)
            ->status($filters['status'] ?? null)
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug, bool $withFields = false): ?Event
    {
        /** @var Event|null $event */
        $event = $this->withSeatCount()
            ->with(['creator:id,name', 'sponsors'])
            ->when($withFields, fn ($q) => $q->with('formFields'))
            ->where('slug', $slug)
            ->first();

        return $event;
    }

    public function findWithRelations(int $id): ?Event
    {
        /** @var Event|null $event */
        $event = $this->withSeatCount()
            ->with(['creator:id,name', 'formFields', 'sponsors'])
            ->find($id);

        return $event;
    }

    public function totalCount(): int
    {
        return $this->query()->count();
    }

    public function countByStatus(string $status): int
    {
        return $this->query()->where('status', $status)->count();
    }

    public function upcomingPublishedCount(): int
    {
        return $this->query()
            ->published()
            ->where('event_date', '>=', Carbon::now()->startOfDay())
            ->count();
    }
}
