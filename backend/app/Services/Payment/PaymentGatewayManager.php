<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\BkashGateway;
use App\Services\Payment\Gateways\NagadGateway;
use App\Services\Payment\Gateways\SslcommerzGateway;
use InvalidArgumentException;

/**
 * Resolves a gateway adapter by its key. Central place to register new gateways.
 */
class PaymentGatewayManager
{
    /**
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    private array $map = [
        'sslcommerz' => SslcommerzGateway::class,
        'bkash'      => BkashGateway::class,
        'nagad'      => NagadGateway::class,
    ];

    public function make(string $gateway): PaymentGatewayInterface
    {
        $key = strtolower($gateway);

        if (! isset($this->map[$key])) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$gateway}].");
        }

        return app($this->map[$key]);
    }

    public function for(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return $this->make($gateway->value);
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        return array_keys($this->map);
    }
}
