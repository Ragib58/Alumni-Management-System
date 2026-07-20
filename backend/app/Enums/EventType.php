<?php

namespace App\Enums;

/**
 * Category of an event.
 */
enum EventType: string
{
    case Reunion         = 'reunion';
    case Seminar         = 'seminar';
    case Workshop        = 'workshop';
    case Sports          = 'sports';
    case CulturalProgram = 'cultural_program';
    case Iftar           = 'iftar';

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
            self::Reunion         => 'Reunion',
            self::Seminar         => 'Seminar',
            self::Workshop        => 'Workshop',
            self::Sports          => 'Sports',
            self::CulturalProgram => 'Cultural Program',
            self::Iftar           => 'Iftar',
        };
    }
}
