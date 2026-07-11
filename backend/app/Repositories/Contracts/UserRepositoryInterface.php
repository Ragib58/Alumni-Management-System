<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Paginated, filterable list for the admin User Management screen.
     *
     * @param array{search?:string,status?:string,role?:string,sort_by?:string,sort_dir?:string} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function countActive(): int;

    public function countByStatus(string $status): int;

    public function totalCount(): int;
}
