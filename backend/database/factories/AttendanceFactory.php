<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $status = fake()->randomElement(AttendanceStatus::values());
        $checkin = $status === AttendanceStatus::NotArrived->value
            ? null
            : Carbon::now()->subDays(fake()->numberBetween(0, 10));

        return [
            'registration_id' => EventRegistration::factory(),
            'event_id'        => Event::factory(),
            'status'          => $status,
            'checkin_time'    => $checkin,
            'checkout_time'   => $status === AttendanceStatus::CheckedOut->value
                ? (clone $checkin)?->addHours(2)
                : null,
            'checked_by'      => User::factory(),
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status'       => AttendanceStatus::CheckedIn->value,
            'checkin_time' => Carbon::now(),
        ]);
    }
}
