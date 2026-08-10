<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (ServiceType $serviceType): void {
            $serviceType->key ??= Str::uuid()->toString();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
