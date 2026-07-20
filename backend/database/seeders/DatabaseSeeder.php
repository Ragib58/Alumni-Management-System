<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            // Phase 2 — events & registrations
            EventPermissionSeeder::class,
            EventSeeder::class,
            // Phase 3 — payments & tickets
            PaymentPermissionSeeder::class,
            PaymentSeeder::class,
            // Phase 4 — attendance, analytics & reports
            AttendancePermissionSeeder::class,
            AttendanceSeeder::class,
            // Phase 5 — notifications, sponsors, settings, activity
            NotificationPermissionSeeder::class,
            SettingsSeeder::class,
            SponsorSeeder::class,
        ]);
    }
}
