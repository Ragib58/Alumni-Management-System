<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AlumniProfile extends Model
{
    /** @use HasFactory<\Database\Factories\AlumniProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'batch',
        'department',
        'session',
        'profession',
        'company',
        'designation',
        'address',
        'profile_photo',
        'bio',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The owning user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL for the stored profile photo (or null).
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        // Already an absolute URL (e.g. seeded avatar) — return as-is.
        if (str_starts_with($this->profile_photo, 'http')) {
            return $this->profile_photo;
        }

        return Storage::disk('public')->url($this->profile_photo);
    }

    /* ----------------------------------------------------------------------
     |  Query scopes for the Alumni Directory (search + filters)
     * -------------------------------------------------------------------- */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('batch', 'ILIKE', $like)
                ->orWhere('department', 'ILIKE', $like)
                ->orWhere('student_id', 'ILIKE', $like)
                ->orWhereHas('user', function (Builder $uq) use ($like) {
                    $uq->where('name', 'ILIKE', $like)
                        ->orWhere('email', 'ILIKE', $like);
                });
        });
    }

    public function scopeBatch(Builder $query, ?string $batch): Builder
    {
        return blank($batch) ? $query : $query->where('batch', $batch);
    }

    public function scopeDepartment(Builder $query, ?string $department): Builder
    {
        return blank($department) ? $query : $query->where('department', $department);
    }

    public function scopeSession(Builder $query, ?string $session): Builder
    {
        return blank($session) ? $query : $query->where('session', $session);
    }

    public function scopeProfession(Builder $query, ?string $profession): Builder
    {
        return blank($profession) ? $query : $query->where('profession', $profession);
    }
}
