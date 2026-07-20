<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title     = fake()->unique()->catchPhrase().' '.fake()->randomElement(['Reunion', 'Meetup', 'Summit', 'Fest']);
        $eventDate = Carbon::now()->addDays(fake()->numberBetween(7, 120));
        $fee       = fake()->randomElement([0, 0, 200, 500, 1000]);

        return [
            'title'              => $title,
            'slug'               => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'banner'             => null,
            'description'        => fake()->paragraphs(3, true),
            'venue'              => fake()->city().' Convention Center',
            'type'               => fake()->randomElement(EventType::values()),
            'event_date'         => $eventDate,
            'registration_start' => Carbon::now()->subDays(2),
            'registration_end'   => (clone $eventDate)->subDays(1),
            'fee'                => $fee,
            'max_capacity'       => fake()->randomElement([null, 50, 100, 200]),
            'status'             => EventStatus::Published->value,
            'created_by'         => User::query()->inRandomOrder()->value('id'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Draft->value]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'     => EventStatus::Completed->value,
            'event_date' => Carbon::now()->subDays(fake()->numberBetween(5, 60)),
        ]);
    }
}
