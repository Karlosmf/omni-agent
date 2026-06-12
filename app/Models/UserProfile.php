<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doc_number',
        'passport_number',
        'birth_date',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'frequent_flyer_number',
        'seat_preference',
        'dietary_restrictions',
        'history_json',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'history_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
