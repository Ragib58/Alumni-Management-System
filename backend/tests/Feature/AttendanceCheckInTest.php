<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\RoleType;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Ticket;
use App\Models\User;
use App\Services\QrService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeTicket(): array
    {
        $event = Event::factory()->create();
        $attendee = User::factory()->create();
        $registration = EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id'  => $attendee->id,
            'status'   => 'confirmed',
        ]);

        $qr = app(QrService::class);
        $token = $qr->generateToken();
        Ticket::factory()->create([
            'registration_id' => $registration->id,
            'qr_token'        => $token,
            'qr_signature'    => $qr->sign($token, $registration),
        ]);

        return [$event, $registration, $token];
    }

    public function test_admin_can_check_in_via_qr(): void
    {
        [$event, $registration, $token] = $this->makeTicket();

        $admin = User::factory()->create();
        $admin->assignRole(RoleType::EventManager->value);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/attendance/check-in', ['qr' => $token]);

        $response->assertCreated()->assertJsonPath('data.status', AttendanceStatus::CheckedIn->value);
        $this->assertDatabaseHas('attendances', [
            'registration_id' => $registration->id,
            'status'          => AttendanceStatus::CheckedIn->value,
        ]);
    }

    public function test_duplicate_check_in_is_prevented(): void
    {
        [, $registration, $token] = $this->makeTicket();

        $admin = User::factory()->create();
        $admin->assignRole(RoleType::EventManager->value);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/attendance/check-in', ['qr' => $token])->assertCreated();

        // Second scan returns 200 with the "already checked in" message.
        $this->postJson('/api/v1/admin/attendance/check-in', ['qr' => $token])
            ->assertOk()
            ->assertJsonPath('message', 'Already checked in.');

        $this->assertSame(1, \App\Models\Attendance::where('registration_id', $registration->id)->count());
    }

    public function test_non_admin_cannot_check_in(): void
    {
        [, , $token] = $this->makeTicket();

        $user = User::factory()->create();
        $user->assignRole(RoleType::Alumni->value);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/admin/attendance/check-in', ['qr' => $token])->assertForbidden();
    }
}
