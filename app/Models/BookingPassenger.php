<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPassenger extends Model
{
    protected $fillable = [
        'booking_id',
        'is_titular',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'document_expiration',
        'birth_date',
        'nationality',
        'phone',
        'email',
        'passport_path',
    ];

    protected $casts = [
        'is_titular' => 'boolean',
        'document_expiration' => 'date',
        'birth_date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
