<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Login        = 'login';
    case Logout       = 'logout';
    case Registration = 'registration';       // user account registration
    case EventRegister = 'event_registration'; // registered for an event
    case Payment      = 'payment';
    case Attendance   = 'attendance';
    case EventUpdate  = 'event_update';
    case EventCreate  = 'event_create';
    case Refund       = 'refund';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
