<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase 2 — event & registration permissions. Additive: it only creates new
 * permissions and grants them to existing roles without touching Phase 1 grants.
 */
class EventPermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'events.view',
        'events.create',
        'events.update',
        'events.delete',
        'events.publish',

        'registrations.view',      // admin: view all registrations
        'registrations.manage',    // admin: change status / payment
        'registrations.create',    // user: register for an event
        'registrations.cancel',    // user: cancel own registration
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin — grant everything (idempotent).
        if ($superAdmin = Role::where('name', RoleType::SuperAdmin->value)->first()) {
            $superAdmin->givePermissionTo($this->permissions);
        }

        // Event Manager — full event management + registration management.
        if ($eventManager = Role::where('name', RoleType::EventManager->value)->first()) {
            $eventManager->givePermissionTo([
                'events.view', 'events.create', 'events.update', 'events.delete', 'events.publish',
                'registrations.view', 'registrations.manage',
            ]);
        }

        // Alumni Member — browse events, register, cancel own.
        if ($alumni = Role::where('name', RoleType::Alumni->value)->first()) {
            $alumni->givePermissionTo([
                'events.view', 'registrations.create', 'registrations.cancel',
            ]);
        }

        // Guest — read-only event browsing.
        if ($guest = Role::where('name', RoleType::Guest->value)->first()) {
            $guest->givePermissionTo(['events.view']);
        }

        Artisan::call('permission:cache-reset');
    }
}
