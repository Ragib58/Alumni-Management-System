<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('email', 'manager@ams.test')->first()
            ?? User::first();

        // ---- A featured, paid reunion with a custom dynamic form ----
        $reunion = Event::firstOrCreate(
            ['slug' => 'grand-alumni-reunion-2024'],
            [
                'title'              => 'Grand Alumni Reunion 2024',
                'banner'             => null,
                'description'        => "Join us for a day of nostalgia, networking and celebration.\n\nReconnect with old friends, meet distinguished alumni, and enjoy cultural performances, dinner and lots more.",
                'venue'              => 'University Central Auditorium, Dhaka',
                'type'               => EventType::Reunion->value,
                'event_date'         => Carbon::now()->addDays(30),
                'registration_start' => Carbon::now()->subDays(5),
                'registration_end'   => Carbon::now()->addDays(25),
                'fee'                => 1000,
                'max_capacity'       => 300,
                'status'             => EventStatus::Published->value,
                'created_by'         => $manager?->id,
            ]
        );

        if ($reunion->formFields()->count() === 0) {
            $reunion->formFields()->createMany([
                [
                    'label' => 'T-Shirt Size', 'name' => 't_shirt_size', 'type' => 'select',
                    'options' => ['S', 'M', 'L', 'XL', 'XXL'], 'is_required' => true, 'sort_order' => 0,
                ],
                [
                    'label' => 'Number of Guests', 'name' => 'guests', 'type' => 'number',
                    'options' => null, 'is_required' => false, 'placeholder' => '0', 'sort_order' => 1,
                ],
                [
                    'label' => 'Dietary Preference', 'name' => 'dietary_preference', 'type' => 'radio',
                    'options' => ['No preference', 'Vegetarian', 'Halal'], 'is_required' => true, 'sort_order' => 2,
                ],
                [
                    'label' => 'Message to Organizers', 'name' => 'message', 'type' => 'textarea',
                    'options' => null, 'is_required' => false, 'sort_order' => 3,
                ],
            ]);
        }

        // ---- A free seminar ----
        Event::firstOrCreate(
            ['slug' => 'career-growth-seminar'],
            [
                'title'              => 'Career Growth Seminar',
                'description'        => 'A free seminar on navigating your career after graduation, featuring industry leaders.',
                'venue'              => 'Seminar Hall B',
                'type'               => EventType::Seminar->value,
                'event_date'         => Carbon::now()->addDays(14),
                'registration_start' => Carbon::now()->subDays(2),
                'registration_end'   => Carbon::now()->addDays(12),
                'fee'                => 0,
                'max_capacity'       => 100,
                'status'             => EventStatus::Published->value,
                'created_by'         => $manager?->id,
            ]
        );

        // ---- A workshop (draft) ----
        Event::firstOrCreate(
            ['slug' => 'hands-on-ai-workshop'],
            [
                'title'              => 'Hands-on AI Workshop',
                'description'        => 'Build your first AI application in a day. Limited seats.',
                'venue'              => 'Computer Lab 3',
                'type'               => EventType::Workshop->value,
                'event_date'         => Carbon::now()->addDays(45),
                'registration_start' => Carbon::now()->addDays(5),
                'registration_end'   => Carbon::now()->addDays(40),
                'fee'                => 500,
                'max_capacity'       => 40,
                'status'             => EventStatus::Draft->value,
                'created_by'         => $manager?->id,
            ]
        );

        // ---- Additional random published events ----
        if (Event::count() < 8) {
            Event::factory(5)->create();
        }

        // ---- Seed some registrations for the reunion ----
        if ($reunion->registrations()->count() === 0) {
            $users = User::whereHas('roles', fn ($q) => $q->where('name', 'alumni_member'))
                ->inRandomOrder()
                ->limit(15)
                ->get();

            foreach ($users as $i => $user) {
                $status = $i % 5 === 0 ? RegistrationStatus::Confirmed : RegistrationStatus::Pending;

                EventRegistration::firstOrCreate(
                    ['event_id' => $reunion->id, 'user_id' => $user->id],
                    [
                        'registration_no' => sprintf('REG-%d-%04d', Carbon::now()->year, 1000 + $i),
                        'status'          => $status->value,
                        'payment_status'  => $status === RegistrationStatus::Confirmed
                            ? PaymentStatus::Paid->value
                            : PaymentStatus::Pending->value,
                        'amount'          => $reunion->fee,
                        'form_response'   => [
                            't_shirt_size'       => collect(['S', 'M', 'L', 'XL'])->random(),
                            'guests'             => (string) random_int(0, 3),
                            'dietary_preference' => collect(['No preference', 'Vegetarian', 'Halal'])->random(),
                        ],
                        'registered_at'   => Carbon::now()->subDays(random_int(0, 4)),
                    ]
                );
            }
        }
    }
}
