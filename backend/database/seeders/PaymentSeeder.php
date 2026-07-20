<?php

namespace Database\Seeders;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Backfills demo payments + tickets for confirmed paid registrations so the
 * admin payment list and revenue dashboard have data to show.
 *
 * Ticket rows are created without rendering a PDF (rendered lazily on download)
 * to avoid a hard dependency on the PDF engine during seeding.
 */
class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var QrService $qr */
        $qr = app(QrService::class);

        $registrations = EventRegistration::with('event')
            ->where('status', RegistrationStatus::Confirmed->value)
            ->where('amount', '>', 0)
            ->get();

        $gateways = PaymentGateway::values();

        foreach ($registrations as $registration) {
            // Payment record
            Payment::firstOrCreate(
                ['registration_id' => $registration->id, 'status' => PaymentStatus::Paid->value],
                [
                    'transaction_id'         => 'TXN-'.Carbon::now()->year.'-'.strtoupper(Str::random(10)),
                    'gateway_transaction_id' => strtoupper(Str::random(12)),
                    'amount'                 => $registration->amount,
                    'currency'               => config('payment.currency', 'BDT'),
                    'gateway'                => $gateways[array_rand($gateways)],
                    'payment_date'           => Carbon::now()->subDays(random_int(0, 5)),
                    'meta'                   => ['seeded' => true],
                ]
            );

            // Ticket record (no PDF yet)
            if (! Ticket::where('registration_id', $registration->id)->exists()) {
                $token = $qr->generateToken();
                Ticket::create([
                    'registration_id' => $registration->id,
                    'ticket_no'       => 'TKT-'.Carbon::now()->year.'-'.strtoupper(Str::random(6)),
                    'qr_token'        => $token,
                    'qr_signature'    => $qr->sign($token, $registration),
                    'issued_at'       => Carbon::now(),
                ]);
            }
        }
    }
}
