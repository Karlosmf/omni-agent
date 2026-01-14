<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'doc_number',
        'passport_number',
        'birth_date',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
