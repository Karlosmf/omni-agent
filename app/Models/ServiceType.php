<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'key',
        'is_active',
        'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
