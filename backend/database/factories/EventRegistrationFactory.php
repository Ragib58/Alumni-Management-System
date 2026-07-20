<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    public function definition(): array
    {
        $status = fake()->randomElement(RegistrationStatus::values());

        return [
            'registration_no' => sprintf('REG-%d-%04d', Carbon::now()->year, fake()->unique()->numberBetween(1, 9999)),
            'event_id'        => Event::factory(),
            'user_id'         => User::factory(),
            'status'          => $status,
            'payment_status'  => fake()->randomElement(PaymentStatus::values()),
            'amount'          => fake()->randomElement([0, 200, 500]),
            'form_response'   => [],
            'registered_at'   => Carbon::now()->subDays(fake()->numberBetween(0, 20)),
            'cancelled_at'    => $status === RegistrationStatus::Cancelled->value ? Carbon::now() : null,
        ];
    }
}
