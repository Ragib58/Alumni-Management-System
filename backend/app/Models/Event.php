<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'banner',
        'description',
        'venue',
        'type',
        'event_date',
        'registration_start',
        'registration_end',
        'fee',
        'max_capacity',
        'status',
        'created_by',
    ];

    protected $appends = [
        'banner_url',
        'is_registration_open',
        'confirmed_count',
        'seats_left',
        'is_full',
    ];

    protected function casts(): array
    {
        return [
            'event_date'         => 'datetime',
            'registration_start' => 'datetime',
            'registration_end'   => 'datetime',
            'fee'                => 'decimal:2',
            'max_capacity'       => 'integer',
            'status'             => EventStatus::class,
            'type'               => EventType::class,
        ];
    }

    /* --------------------------------- Relations -------------------------- */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(EventFormField::class)->orderBy('sort_order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class)->active()->ranked();
    }

    /* ---------------------------- Derived attributes ---------------------- */

    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner) {
            return null;
        }

        if (str_starts_with($this->banner, 'http')) {
            return $this->banner;
        }

        return Storage::disk('public')->url($this->banner);
    }

    /**
     * Count of registrations that occupy a seat (pending + confirmed).
     * Uses the loaded count when available to avoid N+1 queries.
     */
    public function getConfirmedCountAttribute(): int
    {
        if (array_key_exists('active_registrations_count', $this->attributes)) {
            return (int) $this->attributes['active_registrations_count'];
        }

        return $this->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    public function getSeatsLeftAttribute(): ?int
    {
        if (is_null($this->max_capacity)) {
            return null; // unlimited
        }

        return max(0, $this->max_capacity - $this->confirmed_count);
    }

    public function getIsFullAttribute(): bool
    {
        if (is_null($this->max_capacity)) {
            return false;
        }

        return $this->confirmed_count >= $this->max_capacity;
    }

    /**
     * Whether registration is currently open for this event.
     */
    public function getIsRegistrationOpenAttribute(): bool
    {
        if ($this->status !== EventStatus::Published) {
            return false;
        }

        $now = Carbon::now();

        if ($this->registration_start && $now->lt($this->registration_start)) {
            return false;
        }

        if ($this->registration_end && $now->gt($this->registration_end)) {
            return false;
        }

        return ! $this->is_full;
    }

    /* --------------------------------- Scopes ----------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'ILIKE', $like)
                ->orWhere('venue', 'ILIKE', $like)
                ->orWhere('description', 'ILIKE', $like);
        });
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return blank($type) ? $query : $query->where('type', $type);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }
}
