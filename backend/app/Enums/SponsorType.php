<?php

namespace App\Enums;

enum SponsorType: string
{
    case Platinum = 'platinum';
    case Gold     = 'gold';
    case Silver   = 'silver';
    case Bronze   = 'bronze';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Display weight (higher tiers first).
     */
    public function weight(): int
    {
        return match ($this) {
            self::Platinum => 4,
            self::Gold     => 3,
            self::Silver   => 2,
            self::Bronze   => 1,
        };
    }
}
