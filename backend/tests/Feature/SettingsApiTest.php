<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_public_settings_are_available_without_auth(): void
    {
        $this->getJson('/api/v1/public/settings')
            ->assertOk()
            ->assertJsonPath('data.site\\.name', 'Alumni Event Management');
    }

    public function test_super_admin_can_update_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleType::SuperAdmin->value);
        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'site.name', 'value' => 'Renamed Portal'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('settings', ['key' => 'site.name']);
        $this->assertSame('Renamed Portal', app(\App\Services\SettingsService::class)->get('site.name'));
    }

    public function test_event_manager_cannot_update_settings(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleType::EventManager->value);
        Sanctum::actingAs($manager);

        $this->putJson('/api/v1/admin/settings', [
            'settings' => [['key' => 'site.name', 'value' => 'Nope']],
        ])->assertForbidden();
    }
}
