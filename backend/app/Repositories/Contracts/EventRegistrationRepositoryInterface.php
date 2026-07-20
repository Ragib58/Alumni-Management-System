<?php

namespace App\Repositories\Contracts;

use App\Models\EventRegistration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRegistrationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Admin registration list for a given event (or all events).
     *
     * @param array{event_id?:int,status?:string,search?:string} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Registrations belonging to a specific user ("My Registrations").
     */
    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findForUserAndEvent(int $userId, int $eventId): ?EventRegistration;

    /**
     * Number of seat-occupying registrations for an event.
     */
    public function activeCountForEvent(int $eventId): int;

    public function existsForUserAndEvent(int $userId, int $eventId): bool;

    public function totalCount(): int;
}
