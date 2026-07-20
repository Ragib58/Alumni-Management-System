<?php

namespace App\Services;

use App\Enums\FormFieldType;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Jobs\GenerateTicketJob;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $registrations,
        private readonly EventRepositoryInterface $events,
        private readonly NotificationDispatcher $notifications,
        private readonly ActivityLogger $activity,
    ) {
    }

    /* ------------------------------- Listing ------------------------------ */

    public function adminList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->registrations->paginateWithFilters($filters, $perPage);
    }

    public function userList(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->registrations->paginateForUser($userId, $filters, $perPage);
    }

    public function find(int $id): EventRegistration
    {
        /** @var EventRegistration $registration */
        $registration = $this->registrations->findOrFail($id);

        return $registration->load(['user:id,name,email,phone', 'event']);
    }

    /* ----------------------------- Registration --------------------------- */

    /**
     * Execute the full registration flow:
     *   1. capacity & window guards
     *   2. dynamic form validation
     *   3. create registration (status pending, payment pending)
     *
     * @param array<string, mixed>        $formInput  answers keyed by field name
     * @param array<string, UploadedFile> $files      uploaded files keyed by field name
     *
     * @throws ValidationException
     */
    public function register(User $user, Event $event, array $formInput, array $files = []): EventRegistration
    {
        $this->assertRegistrationOpen($event, $user);

        $response = $this->validateAndBuildResponse($event, $formInput, $files);

        return DB::transaction(function () use ($user, $event, $response) {
            // Lock the event row to make the capacity check race-safe.
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->first();

            $activeCount = $this->registrations->activeCountForEvent($locked->id);
            if (! is_null($locked->max_capacity) && $activeCount >= $locked->max_capacity) {
                throw ValidationException::withMessages([
                    'event' => ['Sorry, this event has reached its maximum capacity.'],
                ]);
            }

            $isPaid = (float) $event->fee > 0;

            /** @var EventRegistration $registration */
            $registration = $this->registrations->create([
                'registration_no' => $this->generateRegistrationNo(),
                'event_id'        => $event->id,
                'user_id'         => $user->id,
                // Free events are confirmed immediately; paid events await payment.
                'status'          => $isPaid ? RegistrationStatus::Pending->value : RegistrationStatus::Confirmed->value,
                'payment_status'  => $isPaid ? PaymentStatus::Pending->value : PaymentStatus::Free->value,
                'amount'          => $event->fee,
                'form_response'   => $response,
                'registered_at'   => Carbon::now(),
            ]);

            // Free registrations get a ticket + confirmation straight away.
            if (! $isPaid) {
                GenerateTicketJob::dispatch($registration->id)->afterCommit();
                $this->notifications->registrationConfirmed($registration->fresh(['user', 'event']));
            }

            $this->activity->log(
                \App\Enums\ActivityAction::EventRegister,
                'Registered for '.($event->title).' ('.$registration->registration_no.')',
                $user,
                $registration,
            );

            return $registration->load(['event', 'user:id,name,email,phone']);
        });
    }

    /**
     * A user cancels their own registration.
     */
    public function cancelOwn(User $user, int $registrationId): EventRegistration
    {
        /** @var EventRegistration $registration */
        $registration = $this->registrations->findOrFail($registrationId);

        if ($registration->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'registration' => ['You can only cancel your own registration.'],
            ]);
        }

        if ($registration->status === RegistrationStatus::Cancelled) {
            return $registration;
        }

        $this->registrations->update($registration, [
            'status'       => RegistrationStatus::Cancelled->value,
            'cancelled_at' => Carbon::now(),
        ]);

        return $registration->load('event');
    }

    /**
     * Admin updates a registration's status (and payment status).
     */
    public function updateStatus(int $registrationId, string $status, ?string $paymentStatus = null): EventRegistration
    {
        /** @var EventRegistration $registration */
        $registration = $this->registrations->findOrFail($registrationId);

        $payload = ['status' => $status];

        if ($status === RegistrationStatus::Cancelled->value) {
            $payload['cancelled_at'] = Carbon::now();
        }

        if ($paymentStatus) {
            $payload['payment_status'] = $paymentStatus;
        }

        $this->registrations->update($registration, $payload);

        return $registration->load(['user:id,name,email,phone', 'event:id,title,slug']);
    }

    /* ------------------------------- Guards ------------------------------- */

    /**
     * @throws ValidationException
     */
    private function assertRegistrationOpen(Event $event, User $user): void
    {
        if ($event->status->value !== \App\Enums\EventStatus::Published->value) {
            throw ValidationException::withMessages([
                'event' => ['Registration is not available for this event.'],
            ]);
        }

        $now = Carbon::now();

        if ($event->registration_start && $now->lt($event->registration_start)) {
            throw ValidationException::withMessages([
                'event' => ['Registration has not started yet.'],
            ]);
        }

        if ($event->registration_end && $now->gt($event->registration_end)) {
            throw ValidationException::withMessages([
                'event' => ['The registration deadline has passed.'],
            ]);
        }

        if ($this->registrations->existsForUserAndEvent($user->id, $event->id)) {
            throw ValidationException::withMessages([
                'event' => ['You have already registered for this event.'],
            ]);
        }

        $activeCount = $this->registrations->activeCountForEvent($event->id);
        if (! is_null($event->max_capacity) && $activeCount >= $event->max_capacity) {
            throw ValidationException::withMessages([
                'event' => ['Sorry, this event has reached its maximum capacity.'],
            ]);
        }
    }

    /* ------------------------- Dynamic form validation -------------------- */

    /**
     * Validate the submitted answers against the event's form-field definitions
     * and return the normalized response array to persist.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateAndBuildResponse(Event $event, array $input, array $files): array
    {
        $fields = $event->formFields()->orderBy('sort_order')->get();

        $rules      = [];
        $attributes = [];
        $data       = [];

        foreach ($fields as $field) {
            $key   = 'form_response.'.$field->name;
            $value = $input[$field->name] ?? null;
            $rule  = [$field->is_required ? 'required' : 'nullable'];

            switch ($field->type) {
                case FormFieldType::Email:
                    $rule[] = 'email';
                    break;
                case FormFieldType::Number:
                    $rule[] = 'numeric';
                    break;
                case FormFieldType::Select:
                case FormFieldType::Radio:
                    $rule[] = 'string';
                    if (! empty($field->options)) {
                        $rule[] = \Illuminate\Validation\Rule::in($field->options);
                    }
                    break;
                case FormFieldType::Checkbox:
                    // Multiple choice → array; each item must be a valid option.
                    $rule = [$field->is_required ? 'required' : 'nullable', 'array'];
                    $data[$field->name] = is_array($value) ? $value : ($value !== null ? [$value] : []);
                    if (! empty($field->options)) {
                        $rules[$key.'.*'] = [\Illuminate\Validation\Rule::in($field->options)];
                    }
                    break;
                case FormFieldType::File:
                    // Handled separately below.
                    break;
                case FormFieldType::Text:
                case FormFieldType::Textarea:
                default:
                    $rule[] = 'string';
                    $rule[] = 'max:5000';
                    break;
            }

            if ($field->type === FormFieldType::File) {
                $file = $files[$field->name] ?? null;
                if ($field->is_required && ! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        $key => ['The '.$field->label.' file is required.'],
                    ]);
                }
                if ($file instanceof UploadedFile) {
                    Validator::make(
                        [$field->name => $file],
                        [$field->name => ['file', 'max:5120']] // 5MB
                    )->validate();
                    $data[$field->name] = $file->store('event-uploads', 'public');
                }
                continue;
            }

            if ($field->type !== FormFieldType::Checkbox) {
                $data[$field->name] = $value;
            }

            $rules[$key]      = $rule;
            $attributes[$key] = $field->label;
        }

        Validator::make(['form_response' => $data], $rules, [], $attributes)->validate();

        // Drop null / empty answers for a clean payload.
        return array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * Generate a unique human-friendly registration number: REG-YYYY-NNNN.
     */
    private function generateRegistrationNo(): string
    {
        $year = Carbon::now()->year;

        do {
            $no = sprintf('REG-%d-%04d', $year, random_int(1, 9999));
        } while (EventRegistration::where('registration_no', $no)->exists());

        return $no;
    }
}
