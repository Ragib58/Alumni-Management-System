<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'transaction_id',
        'gateway_transaction_id',
        'amount',
        'currency',
        'gateway',
        'status',
        'payment_date',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'datetime',
            'meta'         => 'array',
            'gateway'      => PaymentGateway::class,
            'status'       => PaymentStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    /* --------------------------------- Scopes ----------------------------- */

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    public function scopeGateway(Builder $query, ?string $gateway): Builder
    {
        return blank($gateway) ? $query : $query->where('gateway', $gateway);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid->value);
    }
}
