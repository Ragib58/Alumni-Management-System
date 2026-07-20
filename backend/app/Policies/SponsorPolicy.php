<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Sponsor;
use App\Models\User;

class SponsorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }
}
