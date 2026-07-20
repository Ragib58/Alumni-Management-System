<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\RoleType;
use App\Models\Attendance;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Marks ~60% of confirmed registrations as attended so the analytics dashboard
 * and reports have realistic numbers.
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->whereIn('name', [
            RoleType::EventManager->value, RoleType::SuperAdmin->value,
        ]))->first();

        $registrations = EventRegistration::where('status', 'confirmed')->get();

        foreach ($registrations as $i => $registration) {
            // ~60% attended; of those ~30% also checked out.
            $roll = $i % 10;
            if ($roll >= 6) {
                continue; // not arrived
            }

            $checkedOut = $roll < 2;
            $checkin = Carbon::now()->subDays(random_int(0, 4))->setTime(random_int(9, 12), random_int(0, 59));

            Attendance::firstOrCreate(
                ['registration_id' => $registration->id],
                [
                    'event_id'      => $registration->event_id,
                    'status'        => $checkedOut ? AttendanceStatus::CheckedOut->value : AttendanceStatus::CheckedIn->value,
                    'checkin_time'  => $checkin,
                    'checkout_time' => $checkedOut ? (clone $checkin)->addHours(3) : null,
                    'checked_by'    => $admin?->id,
                ]
            );
        }
    }
}
