<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository extends BaseRepository implements TicketRepositoryInterface
{
    public function __construct(Ticket $model)
    {
        parent::__construct($model);
    }

    public function findByRegistrationId(int $registrationId): ?Ticket
    {
        /** @var Ticket|null $ticket */
        $ticket = $this->query()
            ->with('registration.event', 'registration.user')
            ->where('registration_id', $registrationId)
            ->first();

        return $ticket;
    }

    public function findByQrToken(string $qrToken): ?Ticket
    {
        /** @var Ticket|null $ticket */
        $ticket = $this->query()
            ->with('registration.event', 'registration.user')
            ->where('qr_token', $qrToken)
            ->first();

        return $ticket;
    }

    public function existsForRegistration(int $registrationId): bool
    {
        return $this->query()->where('registration_id', $registrationId)->exists();
    }

    public function paginateForUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->with(['registration.event:id,title,slug,event_date,venue', 'registration.user:id,name'])
            ->whereHas('registration', fn ($rq) => $rq->where('user_id', $userId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
