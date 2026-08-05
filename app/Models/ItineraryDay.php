<?php

namespace App\Models;

use Database\Factories\ItineraryDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryDay extends Model
{
    /** @use HasFactory<ItineraryDayFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'day_number',
        'date',
        'title',
        'description',
        'location',
        'image_path',
        'services',
    ];

    protected $casts = [
        'date' => 'date',
        'services' => 'array',
        'day_number' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
