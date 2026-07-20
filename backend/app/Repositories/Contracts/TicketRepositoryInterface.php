<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface extends BaseRepositoryInterface
{
    public function findByRegistrationId(int $registrationId): ?Ticket;

    public function findByQrToken(string $qrToken): ?Ticket;

    public function existsForRegistration(int $registrationId): bool;

    /**
     * Tickets belonging to a user (via their registrations).
     */
    public function paginateForUser(int $userId, int $perPage = 10): LengthAwarePaginator;
}
