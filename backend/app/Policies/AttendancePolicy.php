<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Scan QR / mark attendance — Event Managers and Super Admins only.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }
}
