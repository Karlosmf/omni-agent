<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'source',
        'temperature',
        'status',
        'customer_name',
        'customer_phone',
        'raw_message',
        'ai_data',
        'ai_summary',
        'needs_human_attention',
    ];

    protected $casts = [
        'temperature' => LeadTemperature::class,
        'status' => LeadStatus::class,
        'ai_data' => 'array',
        'needs_human_attention' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
