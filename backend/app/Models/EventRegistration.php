<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\EventRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_no',
        'event_id',
        'user_id',
        'status',
        'payment_status',
        'amount',
        'form_response',
        'registered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'form_response'  => 'array',
            'amount'         => 'decimal:2',
            'registered_at'  => 'datetime',
            'cancelled_at'   => 'datetime',
            'status'         => RegistrationStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ----------------------- Phase 3: payments & ticket ------------------- */

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'registration_id');
    }

    /** The most recent payment attempt. */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'registration_id')->latestOfMany();
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'registration_id');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'registration_id');
    }

    /* --------------------------------- Scopes ----------------------------- */

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    public function scopeForEvent(Builder $query, ?int $eventId): Builder
    {
        return blank($eventId) ? $query : $query->where('event_id', $eventId);
    }

    /**
     * Registrations that occupy a seat.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RegistrationStatus::Pending->value,
            RegistrationStatus::Confirmed->value,
        ]);
    }
}
