<?php

namespace App\Models;

use App\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFormField extends Model
{
    /** @use HasFactory<\Database\Factories\EventFormFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'label',
        'name',
        'type',
        'options',
        'is_required',
        'placeholder',
        'help_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options'     => 'array',
            'is_required' => 'boolean',
            'sort_order'  => 'integer',
            'type'        => FormFieldType::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
