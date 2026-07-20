<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\RegistrationStatus;
use App\Models\Attendance;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * QR check-in / check-out workflow.
 *
 *  1. Admin opens scanner
 *  2. Scan QR   → decode { t: token, r: registration_no }
 *  3. Verify registration → token exists, signature valid, event matches,
 *     registration confirmed (not cancelled)
 *  4. Mark attendance → checked_in (+ checkin_time, checked_by); duplicates blocked
 */
class CheckInService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly QrService $qr,
        private readonly ActivityLogger $activity,
    ) {
    }

    /**
     * Resolve a scanned QR value (raw JSON payload or a bare token) to its ticket
     * and run the full verification, then check the participant in.
     *
     * @return array{attendance: Attendance, duplicate: bool}
     *
     * @throws ValidationException
     */
    public function checkInByQr(string $scanned, User $admin, ?int $expectedEventId = null): array
    {
        $token = $this->extractToken($scanned);
        $ticket = $this->resolveTicket($token);

        return $this->checkInTicket($ticket, $admin, $expectedEventId);
    }

    /**
     * Manual check-in by registration id (fallback when a QR can't be scanned).
     *
     * @return array{attendance: Attendance, duplicate: bool}
     */
    public function checkInByRegistration(int $registrationId, User $admin, ?int $expectedEventId = null): array
    {
        $ticket = Ticket::with('registration.event', 'registration.user')
            ->where('registration_id', $registrationId)
            ->first();

        if (! $ticket) {
            throw ValidationException::withMessages([
                'registration' => ['No ticket found for this registration.'],
            ]);
        }

        return $this->checkInTicket($ticket, $admin, $expectedEventId);
    }

    /**
     * Check a participant out (they must be checked in first).
     */
    public function checkOut(int $registrationId, User $admin): Attendance
    {
        $attendance = $this->attendances->findByRegistrationId($registrationId);

        if (! $attendance || $attendance->status === AttendanceStatus::NotArrived) {
            throw ValidationException::withMessages([
                'attendance' => ['This participant has not checked in yet.'],
            ]);
        }

        if ($attendance->status === AttendanceStatus::CheckedOut) {
            return $attendance; // idempotent
        }

        $this->attendances->update($attendance, [
            'status'        => AttendanceStatus::CheckedOut->value,
            'checkout_time' => Carbon::now(),
            'checked_by'    => $admin->id,
        ]);

        return $attendance->fresh(['registration.user', 'checkedBy:id,name']);
    }

    public function listForEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->attendances->paginateForEvent($eventId, $filters, $perPage);
    }

    public function statsForEvent(int $eventId): array
    {
        return $this->attendances->statsForEvent($eventId);
    }

    /* --------------------------------------------------------------------- */

    /**
     * Core verification + check-in, shared by QR and manual flows.
     *
     * @return array{attendance: Attendance, duplicate: bool}
     */
    private function checkInTicket(Ticket $ticket, User $admin, ?int $expectedEventId): array
    {
        $registration = $ticket->registration;

        if (! $registration) {
            throw ValidationException::withMessages([
                'ticket' => ['This ticket is not linked to a registration.'],
            ]);
        }

        // Verify the QR signature — rejects forged / tampered codes.
        if (! $this->qr->verifySignature($ticket->qr_token, $registration, $ticket->qr_signature)) {
            throw ValidationException::withMessages([
                'ticket' => ['Invalid ticket signature.'],
            ]);
        }

        // Scanner is bound to a specific event → reject tickets for other events.
        if ($expectedEventId && $registration->event_id !== $expectedEventId) {
            throw ValidationException::withMessages([
                'event' => ['This ticket belongs to a different event.'],
            ]);
        }

        if ($registration->status === RegistrationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'registration' => ['This registration has been cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($registration, $ticket, $admin) {
            // Lock the attendance row (or create it) to serialize concurrent scans.
            $attendance = Attendance::where('registration_id', $registration->id)
                ->lockForUpdate()
                ->first();

            if ($attendance && $attendance->status !== AttendanceStatus::NotArrived) {
                // Already checked in / out — prevent duplicate check-in.
                return [
                    'attendance' => $attendance->load(['registration.user', 'checkedBy:id,name']),
                    'duplicate'  => true,
                ];
            }

            $attendance = Attendance::updateOrCreate(
                ['registration_id' => $registration->id],
                [
                    'event_id'     => $registration->event_id,
                    'status'       => AttendanceStatus::CheckedIn->value,
                    'checkin_time' => Carbon::now(),
                    'checked_by'   => $admin->id,
                ]
            );

            // Keep the ticket's convenience timestamp in sync.
            if (! $ticket->checked_in_at) {
                $ticket->forceFill(['checked_in_at' => Carbon::now()])->save();
            }

            $this->activity->log(
                \App\Enums\ActivityAction::Attendance,
                'Checked in '.($registration->user?->name ?? 'participant').' for '.($registration->event?->title ?? 'event'),
                $admin,
                $attendance,
                ['registration_no' => $registration->registration_no],
            );

            return [
                'attendance' => $attendance->load(['registration.user', 'checkedBy:id,name']),
                'duplicate'  => false,
            ];
        });
    }

    /**
     * Accept either the raw QR JSON payload or a bare token string.
     */
    private function extractToken(string $scanned): string
    {
        $scanned = trim($scanned);

        if (str_starts_with($scanned, '{')) {
            $data = json_decode($scanned, true);
            if (is_array($data) && ! empty($data['t'])) {
                return (string) $data['t'];
            }
        }

        return $scanned;
    }

    private function resolveTicket(string $token): Ticket
    {
        $ticket = Ticket::with('registration.event', 'registration.user')
            ->where('qr_token', $token)
            ->first();

        if (! $ticket) {
            throw ValidationException::withMessages([
                'ticket' => ['Ticket not found. This QR code is not recognised.'],
            ]);
        }

        return $ticket;
    }
}
