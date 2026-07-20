<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Any authenticated user may browse events.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    /**
     * Who may see the registration list / manage registrations.
     */
    public function manageRegistrations(User $user, Event $event): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }
}
