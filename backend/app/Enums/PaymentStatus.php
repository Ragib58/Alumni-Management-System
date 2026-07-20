<?php

namespace App\Enums;

/**
 * Payment state for a registration. Phase 2 defaults new paid registrations
 * to "pending"; a later phase wires an actual gateway.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid    = 'paid';
    case Failed  = 'failed';
    case Refunded = 'refunded';
    case Free    = 'free';

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
            self::Pending  => 'Payment Pending',
            self::Paid     => 'Paid',
            self::Failed   => 'Failed',
            self::Refunded => 'Refunded',
            self::Free     => 'Free',
        };
    }
}
