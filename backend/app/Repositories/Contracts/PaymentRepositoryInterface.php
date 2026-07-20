<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    public function findByTransactionId(string $transactionId): ?Payment;

    /**
     * Admin payment list with filters.
     *
     * @param array{search?:string,status?:string,gateway?:string,event_id?:int} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Payments belonging to a specific user (via their registrations).
     */
    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Aggregate revenue figures for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function revenueSummary(): array;
}
