<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleType::EventManager->value);

        return $admin;
    }

    public function test_admin_can_create_a_sponsor(): void
    {
        Sanctum::actingAs($this->admin());
        $event = Event::factory()->create();

        $response = $this->postJson('/api/v1/admin/sponsors', [
            'event_id'     => $event->id,
            'name'         => 'TechCorp',
            'sponsor_type' => 'platinum',
            'amount'       => 100000,
            'website'      => 'https://techcorp.example.com',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'TechCorp');
        $this->assertDatabaseHas('sponsors', ['name' => 'TechCorp', 'sponsor_type' => 'platinum']);
    }

    public function test_sponsors_appear_on_public_event_page_ranked_by_tier(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'slug' => 'test-gala']);
        \App\Models\Sponsor::factory()->create(['event_id' => $event->id, 'sponsor_type' => 'bronze', 'is_active' => true]);
        \App\Models\Sponsor::factory()->create(['event_id' => $event->id, 'sponsor_type' => 'platinum', 'is_active' => true]);

        $response = $this->getJson('/api/v1/public/events/test-gala');

        $response->assertOk();
        $sponsors = $response->json('data.sponsors');
        $this->assertCount(2, $sponsors);
        $this->assertSame('platinum', $sponsors[0]['sponsor_type']); // highest tier first
    }

    public function test_alumni_cannot_manage_sponsors(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleType::Alumni->value);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/admin/sponsors', ['name' => 'X', 'sponsor_type' => 'gold'])
            ->assertForbidden();
    }
}
