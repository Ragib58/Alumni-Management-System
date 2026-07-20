<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase 4 — attendance, analytics & report permissions. Additive.
 */
class AttendancePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'attendance.scan',    // open scanner / mark attendance
        'attendance.view',    // view attendance lists
        'analytics.view',     // analytics dashboard
        'reports.view',       // view reports
        'reports.export',     // export excel/csv/pdf
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
            $eventManager->givePermissionTo($this->permissions);
        }

        Artisan::call('permission:cache-reset');
    }
}
