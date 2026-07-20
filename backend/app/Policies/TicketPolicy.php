<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleType::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * View / download a ticket — owner or event managers.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->registration?->user_id === $user->id
            || $user->hasRole(RoleType::EventManager->value);
    }

    public function download(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
