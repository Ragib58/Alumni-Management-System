<?php

namespace App\Models;

use App\Enums\SponsorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Sponsor extends Model
{
    /** @use HasFactory<\Database\Factories\SponsorFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'logo',
        'website',
        'amount',
        'sponsor_type',
        'sort_order',
        'is_active',
    ];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
            'sponsor_type' => SponsorType::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return str_starts_with($this->logo, 'http')
            ? $this->logo
            : Storage::disk('public')->url($this->logo);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, ?int $eventId): Builder
    {
        return blank($eventId) ? $query : $query->where('event_id', $eventId);
    }

    /**
     * Order by tier (platinum → bronze) then sort_order.
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE sponsor_type
                WHEN 'platinum' THEN 4
                WHEN 'gold' THEN 3
                WHEN 'silver' THEN 2
                WHEN 'bronze' THEN 1
                ELSE 0 END DESC")
            ->orderBy('sort_order');
    }
}
