<?php

namespace App\Enums;

/**
 * Supported payment gateways.
 */
enum PaymentGateway: string
{
    case Sslcommerz = 'sslcommerz';
    case Bkash      = 'bkash';
    case Nagad      = 'nagad';

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
            self::Sslcommerz => 'SSLCommerz',
            self::Bkash      => 'bKash',
            self::Nagad      => 'Nagad',
        };
    }
}
