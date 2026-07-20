<?php

namespace Database\Factories;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = fake()->randomElement(PaymentStatus::values());

        return [
            'registration_id'        => EventRegistration::factory(),
            'transaction_id'         => 'TXN-'.Carbon::now()->year.'-'.strtoupper(Str::random(10)),
            'gateway_transaction_id' => strtoupper(Str::random(12)),
            'amount'                 => fake()->randomElement([200, 500, 1000]),
            'currency'               => 'BDT',
            'gateway'                => fake()->randomElement(PaymentGateway::values()),
            'status'                 => $status,
            'payment_date'           => $status === PaymentStatus::Paid->value ? Carbon::now() : null,
            'meta'                   => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status'       => PaymentStatus::Paid->value,
            'payment_date' => Carbon::now(),
        ]);
    }
}
