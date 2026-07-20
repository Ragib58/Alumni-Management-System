<?php

namespace App\Enums;

/**
 * Attendance lifecycle for an event registration.
 */
enum AttendanceStatus: string
{
    case NotArrived = 'not_arrived';
    case CheckedIn  = 'checked_in';
    case CheckedOut = 'checked_out';

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
            self::NotArrived => 'Not Arrived',
            self::CheckedIn  => 'Checked In',
            self::CheckedOut => 'Checked Out',
        };
    }
}
