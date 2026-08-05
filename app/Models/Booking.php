<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->file_number)) {
                $year = now()->year;
                $prefix = "LPN-{$year}-";

                // Find max number for current year
                $lastBooking = self::where('file_number', 'like', "{$prefix}%")
                    ->orderByRaw('CAST(SUBSTR(file_number, LENGTH(?) + 1) AS UNSIGNED) DESC', [$prefix])
                    ->first();

                $number = 1;
                if ($lastBooking) {
                    $parts = explode('-', $lastBooking->file_number);
                    $number = intval(end($parts)) + 1;
                }

                $booking->file_number = $prefix.str_pad($number, 5, '0', STR_PAD_LEFT);
            }

            if (empty($booking->public_token)) {
                $booking->public_token = Str::random(48);
            }
        });
    }

    protected $fillable = [
        'lead_id',
        'customer_id',
        'agent_id',
        'file_number',
        'public_token',
        'holder_name',
        'destination',
        'nights',
        'passengers',
        'currency',
        'exchange_rate',
        'total_cost',
        'total_sell',
        'profit',
        'status',
        'travel_date',
        'valid_until',
        'internal_notes',
        'notes',
        'vouchers',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'travel_date' => 'date',
        'valid_until' => 'date',
        'total_cost' => 'decimal:2',
        'total_sell' => 'decimal:2',
        'profit' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'vouchers' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function bookingPassengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function itineraryDays()
    {
        return $this->hasMany(ItineraryDay::class)->orderBy('day_number');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && Carbon::parse($this->valid_until)->isPast();
    }

    public function calculateProfit(): float
    {
        return (float) ($this->total_sell - $this->total_cost);
    }

    /**
     * Get the public shareable URL for this booking proposal.
     */
    public function publicUrl(): string
    {
        if (empty($this->public_token)) {
            $this->update(['public_token' => Str::random(48)]);
        }

        return route('booking.public', $this->public_token);
    }
}
