<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\User;

class UserPolicy
{
    /**
     * Super Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleType::SuperAdmin->value, RoleType::EventManager->value]);
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasAnyRole([RoleType::SuperAdmin->value, RoleType::EventManager->value]);
    }

    public function create(User $user): bool
    {
        // Only Super Admin (handled in before()). Event Managers cannot create users.
        return false;
    }

    public function update(User $user, User $model): bool
    {
        // Event Managers may edit non-admin users; Super Admin handled in before().
        if ($user->hasRole(RoleType::EventManager->value)) {
            return ! $model->hasRole(RoleType::SuperAdmin->value);
        }

        return false;
    }

    public function updateStatus(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false; // cannot change your own status
        }

        return $user->hasRole(RoleType::EventManager->value)
            && ! $model->hasRole(RoleType::SuperAdmin->value);
    }

    public function delete(User $user, User $model): bool
    {
        // Only Super Admin (before()); never allow deleting yourself.
        return $user->id !== $model->id ? false : false;
    }
}
