<?php

namespace App\Models;

use App\Enums\PriceBasis;
use App\Enums\SingleSupplementType;
use Carbon\Carbon;
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
        'price_basis',
        'price_basis_min',
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
        'single_supplement_type',
        'single_supplement_amount',
        'triple_reduction_percent',
        'children_policies',
        'seasons',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'gallery' => 'array',
            'itinerary' => 'array',
            'services' => 'array',
            'children_policies' => 'array',
            'seasons' => 'array',
            'is_active' => 'boolean',
            'price_from' => 'decimal:2',
            'price_basis' => PriceBasis::class,
            'price_basis_min' => 'integer',
            'single_supplement_type' => SingleSupplementType::class,
            'single_supplement_amount' => 'decimal:2',
            'triple_reduction_percent' => 'decimal:2',
        ];
    }

    /**
     * Calculates the indicative total sell price for a given number of passengers.
     *
     * Uses the package-level price_basis as the default occupancy unit.
     * Individual services within the package may override this with their own basis.
     *
     * Fixed-price basis → always returns price_from (price doesn't scale).
     * Unit-based basis  → price_from × ceil(passengers / basisSize) × basisSize
     */
    public function calculatePriceForPassengers(int $passengers): float
    {
        $basis = $this->price_basis ?? PriceBasis::PorPersona;

        return (float) ($this->price_from * $basis->multiplierFor($passengers));
    }

    /**
     * Returns the active price_from for a given travel date, taking seasons into account.
     * Falls back to the package's default price_from if no season matches.
     */
    public function priceForDate(?string $travelDate): float
    {
        if (empty($this->seasons) || ! $travelDate) {
            return (float) $this->price_from;
        }

        $date = Carbon::parse($travelDate);

        foreach ($this->seasons as $season) {
            $from = Carbon::parse($season['from'] ?? null);
            $to = Carbon::parse($season['to'] ?? null);

            if ($date->between($from, $to)) {
                return (float) ($season['price_from'] ?? $this->price_from);
            }
        }

        return (float) $this->price_from;
    }

    /**
     * Calculates the single supplement amount given a base unit price.
     * Returns 0 if no supplement is configured.
     */
    public function singleSupplementFor(float $baseUnitPrice): float
    {
        if (! $this->single_supplement_type || ! $this->single_supplement_amount) {
            return 0.0;
        }

        return match ($this->single_supplement_type) {
            SingleSupplementType::Fixed => (float) $this->single_supplement_amount,
            SingleSupplementType::Percent => $baseUnitPrice * ((float) $this->single_supplement_amount / 100),
        };
    }
}
