<?php

namespace Database\Factories;

use App\Models\EventRegistration;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $token = Str::lower(Str::random(40));

        return [
            'registration_id' => EventRegistration::factory(),
            'ticket_no'       => 'TKT-'.Carbon::now()->year.'-'.strtoupper(Str::random(6)),
            'qr_token'        => $token,
            'qr_signature'    => hash_hmac('sha256', $token, (string) config('app.key', 'testing')),
            'issued_at'       => Carbon::now(),
        ];
    }
}
