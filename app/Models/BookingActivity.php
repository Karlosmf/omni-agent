<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingActivity extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'type',
        'description',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity for a booking.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(Booking $booking, string $type, string $description, ?array $properties = null): self
    {
        return self::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
