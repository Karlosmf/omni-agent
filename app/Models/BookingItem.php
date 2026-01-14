<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    /** @use HasFactory<\Database\Factories\BookingItemFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'description',
        'supplier_name',
        'cost_usd',
        'sell_usd',
    ];

    protected $casts = [
        'type' => ServiceType::class,
        'cost_usd' => 'decimal:2',
        'sell_usd' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
