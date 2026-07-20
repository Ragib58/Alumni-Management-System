<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Admin payment list / revenue dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $payment->registration?->user_id === $user->id
            || $user->hasRole(RoleType::EventManager->value);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasRole(RoleType::EventManager->value);
    }
}
