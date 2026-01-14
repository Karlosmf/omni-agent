<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'customer_id',
        'file_number',
        'holder_name',
        'total_cost_usd',
        'total_sell_usd',
        'profit_usd',
        'status',
        'travel_date',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'travel_date' => 'date',
        'total_cost_usd' => 'decimal:2',
        'total_sell_usd' => 'decimal:2',
        'profit_usd' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function calculateProfit(): float
    {
        return (float) ($this->total_sell_usd - $this->total_cost_usd);
    }
}
