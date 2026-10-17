<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\RateSource;
use App\Exceptions\MissingExchangeRateException;
use App\Services\CurrencyService;
use Database\Factories\BookingItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    /** @use HasFactory<BookingItemFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'supplier_id',
        'service_type_id',
        'description',
        'currency',
        'exchange_rate',
        'rate_source',
        'cost',
        'sell',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'sell' => 'decimal:2',
        'currency' => Currency::class,
        'exchange_rate' => 'decimal:4',
        'rate_source' => RateSource::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingItem $item) {
            $item->ensureExchangeRate();
        });
    }

    /**
     * Non-USD items must carry their own rate (units of currency per 1 USD).
     * When it is missing it is filled from today's default dollar; if that is
     * unavailable the item cannot be saved, so amounts are never converted 1:1.
     */
    public function ensureExchangeRate(): void
    {
        $currency = $this->currency instanceof Currency ? $this->currency : Currency::tryFrom((string) $this->currency);

        if ($currency === null || $currency === Currency::USD) {
            $this->exchange_rate = 1;
            $this->rate_source = null;

            return;
        }

        // A rate of exactly 1 for a non-USD currency is the legacy "unquoted" default, not a real quote.
        if ((float) $this->exchange_rate > 0 && (float) $this->exchange_rate !== 1.0) {
            $this->rate_source ??= RateSource::Custom;

            return;
        }

        $source = get_agency_settings()?->default_rate_source ?? RateSource::Oficial;
        $rate = app(CurrencyService::class)->unitsPerUsd($currency, $source);

        if (! $rate) {
            throw new MissingExchangeRateException("Cargá la cotización del servicio en {$currency->value}: no hay cotización disponible.");
        }

        $this->exchange_rate = $rate;
        $this->rate_source = $source;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
