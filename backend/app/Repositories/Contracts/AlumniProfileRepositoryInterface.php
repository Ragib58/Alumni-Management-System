<?php

namespace App\Repositories\Contracts;

use App\Models\AlumniProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AlumniProfileRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUserId(int $userId): ?AlumniProfile;

    /**
     * Update-or-create the profile for a given user.
     */
    public function upsertForUser(int $userId, array $attributes): AlumniProfile;

    /**
     * Directory listing: search by name/batch/department, filter by session/profession.
     *
     * @param array{search?:string,batch?:string,department?:string,session?:string,profession?:string,sort_by?:string,sort_dir?:string} $filters
     */
    public function directory(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    /**
     * Batch distribution counts, e.g. [['batch' => '2015', 'total' => 12], ...].
     *
     * @return array<int, array{batch:string,total:int}>
     */
    public function batchDistribution(): array;

    /**
     * Distinct non-null values for a given filterable column.
     *
     * @return array<int, string>
     */
    public function distinctValues(string $column): array;
}
