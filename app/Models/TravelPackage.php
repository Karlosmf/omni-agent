<?php

namespace App\Models;

use Database\Factories\TravelPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TravelPackage extends Model
{
    /** @use HasFactory<TravelPackageFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TravelPackage $package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->title);
            }
        });
    }

    protected $fillable = [
        'title',
        'slug',
        'destination',
        'nights',
        'tags',
        'price_from',
        'currency',
        'cover_image',
        'gallery',
        'summary',
        'description',
        'itinerary',
        'services',
        'included',
        'excluded',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'gallery' => 'array',
        'itinerary' => 'array',
        'services' => 'array',
        'is_active' => 'boolean',
        'price_from' => 'decimal:2',
    ];

    public function getPriceBasisAttribute()
    {
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists('travel_packages_extras.json')) {
            $extras = json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get('travel_packages_extras.json'), true);
            return $extras[$this->id]['price_basis'] ?? 'por persona';
        }
        return 'por persona';
    }
}
