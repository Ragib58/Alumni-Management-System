<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\AlumniProfile;
use App\Models\User;

class AlumniProfilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Any authenticated, active user can browse the directory.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AlumniProfile $profile): bool
    {
        return true;
    }

    public function update(User $user, AlumniProfile $profile): bool
    {
        // Owners can edit their own profile; Event Managers can edit any.
        return $user->id === $profile->user_id
            || $user->hasRole(RoleType::EventManager->value);
    }

    public function delete(User $user, AlumniProfile $profile): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }
}
