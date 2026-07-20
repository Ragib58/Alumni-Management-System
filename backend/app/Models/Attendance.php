<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'event_id',
        'status',
        'checkin_time',
        'checkout_time',
        'checked_by',
    ];

    protected function casts(): array
    {
        return [
            'checkin_time'  => 'datetime',
            'checkout_time' => 'datetime',
            'status'        => AttendanceStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function isCheckedIn(): bool
    {
        return $this->status === AttendanceStatus::CheckedIn;
    }

    /* --------------------------------- Scopes ----------------------------- */

    public function scopeForEvent(Builder $query, ?int $eventId): Builder
    {
        return blank($eventId) ? $query : $query->where('event_id', $eventId);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    /** Records that count as "attended" (checked in or out). */
    public function scopeAttended(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AttendanceStatus::CheckedIn->value,
            AttendanceStatus::CheckedOut->value,
        ]);
    }
}
