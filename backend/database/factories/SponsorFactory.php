<?php

namespace Database\Factories;

use App\Enums\SponsorType;
use App\Models\Event;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'event_id'     => Event::factory(),
            'name'         => fake()->company(),
            'logo'         => null,
            'website'      => fake()->url(),
            'amount'       => fake()->randomElement([10000, 25000, 50000, 100000]),
            'sponsor_type' => fake()->randomElement(SponsorType::values()),
            'sort_order'   => 0,
            'is_active'    => true,
        ];
    }
}
