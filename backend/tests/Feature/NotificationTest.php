<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\RegistrationConfirmedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registering_for_a_free_event_sends_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->assignRole(RoleType::Alumni->value);
        Sanctum::actingAs($user);

        $event = Event::factory()->create([
            'status'             => 'published',
            'fee'                => 0,
            'registration_start' => now()->subDay(),
            'registration_end'   => now()->addDay(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/register", ['form_response' => []])
            ->assertCreated();

        Notification::assertSentTo($user, RegistrationConfirmedNotification::class);
    }

    public function test_user_can_list_and_mark_notifications_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleType::Alumni->value);

        // Seed one in-app notification directly.
        $user->notify(new RegistrationConfirmedNotification(
            EventRegistration::factory()->create(['user_id' => $user->id])
        ));

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/v1/notifications')->assertOk();
        $id = $list->json('data.0.id');

        $this->patchJson("/api/v1/notifications/{$id}/read")->assertOk()->assertJsonPath('data.read', true);
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread_count', 0);
    }
}
