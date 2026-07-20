<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array{action?:string,user_id?:int,search?:string} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 25): LengthAwarePaginator;
}
