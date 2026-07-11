<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Repositories\Contracts\AlumniProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AlumniProfileRepositoryInterface $profiles,
    ) {
    }

    /**
     * Aggregate statistics for the admin dashboard.
     *
     * @return array{
     *   total_alumni:int,
     *   total_users:int,
     *   total_active_users:int,
     *   total_inactive_users:int,
     *   total_suspended_users:int,
     *   batch_distribution: array<int, array{batch:string,total:int}>
     * }
     */
    public function statistics(): array
    {
        return [
            'total_alumni'          => $this->profiles->totalCount(),
            'total_users'           => $this->users->totalCount(),
            'total_active_users'    => $this->users->countActive(),
            'total_inactive_users'  => $this->users->countByStatus(UserStatus::Inactive->value),
            'total_suspended_users' => $this->users->countByStatus(UserStatus::Suspended->value),
            'batch_distribution'    => $this->profiles->batchDistribution(),
        ];
    }
}
