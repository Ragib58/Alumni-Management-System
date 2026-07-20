<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function findByRegistrationId(int $registrationId): ?Attendance;

    /**
     * Attendance list for an event (joined with registration + user).
     *
     * @param array{status?:string,search?:string} $filters
     */
    public function paginateForEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return array{total:int, checked_in:int, checked_out:int, not_arrived:int}
     */
    public function statsForEvent(int $eventId): array;

    public function totalAttendedCount(): int;
}
