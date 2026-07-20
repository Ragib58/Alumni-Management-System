<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Models\Event;
use App\Models\EventFormField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventFormField>
 */
class EventFormFieldFactory extends Factory
{
    protected $model = EventFormField::class;

    public function definition(): array
    {
        $label = fake()->randomElement(['T-Shirt Size', 'Dietary Preference', 'Guests', 'Comments']);
        $type  = fake()->randomElement(FormFieldType::values());

        return [
            'event_id'    => Event::factory(),
            'label'       => $label,
            'name'        => Str::slug($label, '_'),
            'type'        => $type,
            'options'     => FormFieldType::from($type)->requiresOptions()
                ? ['Option A', 'Option B', 'Option C']
                : null,
            'is_required' => fake()->boolean(40),
            'placeholder' => null,
            'help_text'   => null,
            'sort_order'  => 0,
        ];
    }
}
