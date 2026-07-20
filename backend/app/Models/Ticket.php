<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'ticket_no',
        'qr_token',
        'qr_signature',
        'pdf_path',
        'issued_at',
        'emailed_at',
        'checked_in_at',
    ];

    protected $hidden = [
        // Never expose the signing signature to clients.
        'qr_signature',
    ];

    protected function casts(): array
    {
        return [
            'issued_at'     => 'datetime',
            'emailed_at'    => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }

    public function isCheckedIn(): bool
    {
        return ! is_null($this->checked_in_at);
    }
}
