<?php

namespace App\Enums;

/**
 * Lifecycle status of an event.
 */
enum EventStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Closed    = 'closed';
    case Completed = 'completed';

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
            self::Draft     => 'Draft',
            self::Published => 'Published',
            self::Closed    => 'Closed',
            self::Completed => 'Completed',
        };
    }
}
