<?php

namespace App\Repositories\Eloquent;

use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventRegistrationRepository extends BaseRepository implements EventRegistrationRepositoryInterface
{
    public function __construct(EventRegistration $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['user:id,name,email,phone', 'event:id,title,slug'])
            ->forEvent($filters['event_id'] ?? null)
            ->status($filters['status'] ?? null)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('registration_no', 'ILIKE', $like)
                        ->orWhereHas('user', function ($uq) use ($like) {
                            $uq->where('name', 'ILIKE', $like)
                                ->orWhere('email', 'ILIKE', $like);
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['event' => fn ($q) => $q->withCount([
                'registrations as active_registrations_count' => fn ($rq) => $rq->active(),
            ])])
            ->where('user_id', $userId)
            ->status($filters['status'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForUserAndEvent(int $userId, int $eventId): ?EventRegistration
    {
        /** @var EventRegistration|null $registration */
        $registration = $this->query()
            ->where('user_id', $userId)
            ->where('event_id', $eventId)
            ->first();

        return $registration;
    }

    public function activeCountForEvent(int $eventId): int
    {
        return $this->query()
            ->where('event_id', $eventId)
            ->active()
            ->count();
    }

    public function existsForUserAndEvent(int $userId, int $eventId): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('event_id', $eventId)
            ->exists();
    }

    public function totalCount(): int
    {
        return $this->query()->count();
    }
}
