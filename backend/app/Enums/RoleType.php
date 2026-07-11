<?php

namespace App\Enums;

/**
 * Canonical role names used across the RBAC layer.
 */
enum RoleType: string
{
    case SuperAdmin   = 'super_admin';
    case EventManager = 'event_manager';
    case Alumni       = 'alumni_member';
    case Guest        = 'guest';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin   => 'Super Admin',
            self::EventManager => 'Event Manager',
            self::Alumni       => 'Alumni Member',
            self::Guest        => 'Guest',
        };
    }
}
