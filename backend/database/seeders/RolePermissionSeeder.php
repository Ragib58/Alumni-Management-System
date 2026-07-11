<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission catalogue for Phase 1.
     *
     * @var array<int, string>
     */
    private array $permissions = [
        // User management
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.update-status',

        // Alumni
        'alumni.view',
        'alumni.update',
        'alumni.delete',

        // Dashboard
        'dashboard.view',

        // Profile (own)
        'profile.view',
        'profile.update',
    ];

    public function run(): void
    {
        // Reset cached roles and permissions.
        Artisan::call('permission:cache-reset');

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ---- Super Admin: everything ----
        $superAdmin = Role::firstOrCreate(['name' => RoleType::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ---- Event Manager ----
        $eventManager = Role::firstOrCreate(['name' => RoleType::EventManager->value, 'guard_name' => 'web']);
        $eventManager->syncPermissions([
            'users.view',
            'users.update',
            'users.update-status',
            'alumni.view',
            'alumni.update',
            'alumni.delete',
            'dashboard.view',
            'profile.view',
            'profile.update',
        ]);

        // ---- Alumni Member ----
        $alumni = Role::firstOrCreate(['name' => RoleType::Alumni->value, 'guard_name' => 'web']);
        $alumni->syncPermissions([
            'alumni.view',
            'profile.view',
            'profile.update',
        ]);

        // ---- Guest (read-only directory) ----
        $guest = Role::firstOrCreate(['name' => RoleType::Guest->value, 'guard_name' => 'web']);
        $guest->syncPermissions([
            'alumni.view',
        ]);

        Artisan::call('permission:cache-reset');
    }
}
