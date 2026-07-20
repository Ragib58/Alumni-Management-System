<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Admin registration listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function view(User $user, EventRegistration $registration): bool
    {
        return $registration->user_id === $user->id
            || $user->hasRole(RoleType::EventManager->value);
    }

    /**
     * Admin changes registration/payment status.
     */
    public function updateStatus(User $user, EventRegistration $registration): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    /**
     * A user cancels their own registration.
     */
    public function cancel(User $user, EventRegistration $registration): bool
    {
        return $registration->user_id === $user->id
            || $user->hasRole(RoleType::EventManager->value);
    }
}
