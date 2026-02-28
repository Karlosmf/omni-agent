<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    /** @use HasFactory<\Database\Factories\BookingItemFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'supplier_id',
        'service_type_id',
        'description',
        'currency',
        'exchange_rate',
        'cost',
        'sell',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'sell' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
