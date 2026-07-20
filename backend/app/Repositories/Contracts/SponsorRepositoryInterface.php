<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SponsorRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array{search?:string,event_id?:int,sponsor_type?:string} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, \App\Models\Sponsor>
     */
    public function activeForEvent(int $eventId): Collection;
}
