<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'currency',
        'amount',
        'exchange_rate',
        'amount_usd_fixed',
        'method',
        'notes',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'currency' => Currency::class,
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_usd_fixed' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
