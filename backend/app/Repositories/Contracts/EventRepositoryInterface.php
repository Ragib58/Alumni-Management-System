<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginated event list for admins (all statuses) or the public/authenticated
     * catalogue (published only) depending on $filters['published_only'].
     *
     * @param array{search?:string,type?:string,status?:string,published_only?:bool,upcoming?:bool,sort_by?:string,sort_dir?:string} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 12): LengthAwarePaginator;

    public function findBySlug(string $slug, bool $withFields = false): ?Event;

    public function findWithRelations(int $id): ?Event;

    public function totalCount(): int;

    public function countByStatus(string $status): int;

    public function upcomingPublishedCount(): int;
}
