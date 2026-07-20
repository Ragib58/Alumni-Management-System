<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase 5 — sponsor, settings, activity-log & notification permissions.
 */
class NotificationPermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'sponsors.view', 'sponsors.manage',
        'settings.view', 'settings.manage',
        'activity.view',
        'notifications.view',
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($superAdmin = Role::where('name', RoleType::SuperAdmin->value)->first()) {
            $superAdmin->givePermissionTo($this->permissions);
        }

        if ($eventManager = Role::where('name', RoleType::EventManager->value)->first()) {
            $eventManager->givePermissionTo([
                'sponsors.view', 'sponsors.manage', 'activity.view', 'notifications.view',
            ]);
        }

        // Everyone signed-in can see their own in-app notifications.
        foreach ([RoleType::Alumni->value, RoleType::Guest->value] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo('notifications.view');
            }
        }

        Artisan::call('permission:cache-reset');
    }
}
