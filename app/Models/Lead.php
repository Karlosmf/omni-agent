<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'source',
        'travel_package_id',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_budget',
        'raw_message',
        'ai_data',
        'ai_summary',
        'needs_human_attention',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'ai_data' => 'array',
        'needs_human_attention' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class);
    }
}
