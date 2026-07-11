<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Enums\UserStatus;
use App\Models\AlumniProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------- Super Admin ----------------
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ams.test'],
            [
                'name'     => 'Super Admin',
                'phone'    => '+8801700000000',
                'password' => Hash::make('Password123!'),
                'status'   => UserStatus::Active->value,
            ]
        );
        $superAdmin->syncRoles([RoleType::SuperAdmin->value]);

        // ---------------- Event Manager ----------------
        $eventManager = User::firstOrCreate(
            ['email' => 'manager@ams.test'],
            [
                'name'     => 'Event Manager',
                'phone'    => '+8801700000001',
                'password' => Hash::make('Password123!'),
                'status'   => UserStatus::Active->value,
            ]
        );
        $eventManager->syncRoles([RoleType::EventManager->value]);

        // ---------------- Demo Alumni Member ----------------
        $alumniUser = User::firstOrCreate(
            ['email' => 'alumni@ams.test'],
            [
                'name'     => 'Rakib Hasan',
                'phone'    => '+8801700000002',
                'password' => Hash::make('Password123!'),
                'status'   => UserStatus::Active->value,
            ]
        );
        $alumniUser->syncRoles([RoleType::Alumni->value]);

        AlumniProfile::firstOrCreate(
            ['user_id' => $alumniUser->id],
            [
                'student_id'  => 'CSE-1501',
                'batch'       => '2015',
                'department'  => 'CSE',
                'session'     => '2015-2016',
                'profession'  => 'Software Engineer',
                'company'     => 'TechCorp Ltd.',
                'designation' => 'Senior Engineer',
                'address'     => 'Dhaka, Bangladesh',
                'bio'         => 'Full-stack engineer and proud alumnus.',
            ]
        );

        // ---------------- Bulk demo alumni for the directory ----------------
        User::factory(40)
            ->has(AlumniProfile::factory(), 'alumniProfile')
            ->create()
            ->each(fn (User $u) => $u->assignRole(RoleType::Alumni->value));

        // A few inactive/suspended accounts to exercise dashboard stats.
        User::factory(4)->inactive()
            ->has(AlumniProfile::factory(), 'alumniProfile')
            ->create()
            ->each(fn (User $u) => $u->assignRole(RoleType::Alumni->value));

        User::factory(2)->suspended()
            ->has(AlumniProfile::factory(), 'alumniProfile')
            ->create()
            ->each(fn (User $u) => $u->assignRole(RoleType::Alumni->value));
    }
}
