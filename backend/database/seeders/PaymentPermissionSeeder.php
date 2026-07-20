<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase 3 — payment & ticket permissions. Additive: only creates new
 * permissions and grants them to existing roles.
 */
class PaymentPermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'payments.pay',        // user: pay for own registration
        'payments.view',       // admin: view all payments / transactions
        'payments.refund',     // admin: refund a payment
        'revenue.view',        // admin: revenue dashboard
        'tickets.view',        // user: view/download own ticket
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
                'payments.view', 'payments.refund', 'revenue.view',
            ]);
        }

        if ($alumni = Role::where('name', RoleType::Alumni->value)->first()) {
            $alumni->givePermissionTo([
                'payments.pay', 'tickets.view',
            ]);
        }

        Artisan::call('permission:cache-reset');
    }
}
