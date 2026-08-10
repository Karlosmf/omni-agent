<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(LeadObserver::class)]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'agent_id',
        'source',
        'travel_package_id',
        'status',
        'customer_budget',
        'raw_message',
        'ai_data',
        'ai_summary',
        'needs_human_attention',
        'temperature',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'temperature' => LeadTemperature::class,
        'ai_data' => 'array',
        'needs_human_attention' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
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
