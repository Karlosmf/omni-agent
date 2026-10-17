<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\RateSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CurrencyService
{
    protected string $storagePath = 'currency_rates.json';

    /**
     * Fetches rates from Dolar API (Faster & more stable than scraping).
     *
     * @return array{currencies: array<string, array{name: string, buy: float, sell: float}>, dollars: array<string, array{name: string, buy: float, sell: float}>, updated_at: string, source: string}|null
     */
    public function fetchRates(): ?array
    {
        $currencies = [];
        $dollars = [];
        $latestUpdate = null;

        $trackUpdate = function (array $json) use (&$latestUpdate): void {
            if (isset($json['fechaActualizacion'])) {
                $date = Carbon::parse($json['fechaActualizacion']);
                if (! $latestUpdate || $date->gt($latestUpdate)) {
                    $latestUpdate = $date;
                }
            }
        };

        try {
            $response = Http::timeout(10)->get('https://dolarapi.com/v1/dolares');

            if ($response->successful()) {
                $houses = collect($response->json())->keyBy('casa');

                foreach (RateSource::quoted() as $source) {
                    $json = $houses->get($source->dolarApiHouse());

                    if ($json && (float) ($json['venta'] ?? 0) > 0) {
                        $dollars[$source->value] = [
                            'name' => $source->getLabel(),
                            'buy' => (float) ($json['compra'] ?? 0),
                            'sell' => (float) $json['venta'],
                        ];
                        $trackUpdate($json);
                    }
                }

                if (isset($dollars[RateSource::Oficial->value])) {
                    $currencies['USD'] = $dollars[RateSource::Oficial->value];
                }
            }

            foreach (['EUR' => 'https://dolarapi.com/v1/cotizaciones/eur', 'BRL' => 'https://dolarapi.com/v1/cotizaciones/brl'] as $key => $url) {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $json = $response->json();

                    $currencies[$key] = [
                        'name' => $json['nombre'] ?? $key,
                        'buy' => (float) ($json['compra'] ?? 0),
                        'sell' => (float) ($json['venta'] ?? 0),
                    ];
                    $trackUpdate($json);
                }
            }
        } catch (\Exception $e) {
            Log::error('CurrencyService: API Error: '.$e->getMessage());
        }

        if (count($currencies) === 0 && count($dollars) === 0) {
            return null;
        }

        return [
            'currencies' => $currencies,
            'dollars' => $dollars,
            'updated_at' => ($latestUpdate ?? now())->toDateTimeString(),
            'source' => 'Dolar API',
        ];
    }

    /**
     * Updates the local JSON storage with fresh rates.
     */
    public function updateLocalRates(): bool
    {
        $data = $this->fetchRates();

        if ($data) {
            Storage::put($this->storagePath, json_encode($data, JSON_PRETTY_PRINT));

            return true;
        }

        return false;
    }

    /**
     * Retrieves a stored exchange rate (ARS per unit of $currency), or null when it is unknown.
     */
    public function getRate(string $currency = 'USD', string $type = 'sell'): ?float
    {
        $rate = (float) ($this->getAllData()['currencies'][$currency][$type] ?? 0);

        return $rate > 0 ? $rate : null;
    }

    /**
     * ARS per USD for the given quoted dollar, or null when it is unknown (or the source is manual).
     */
    public function getDollarRate(RateSource $source): ?float
    {
        if ($source === RateSource::Custom) {
            return null;
        }

        $data = $this->getAllData();
        $rate = (float) ($data['dollars'][$source->value]['sell'] ?? 0);

        if ($rate <= 0 && $source === RateSource::Oficial) {
            $rate = (float) ($data['currencies']['USD']['sell'] ?? 0);
        }

        return $rate > 0 ? $rate : null;
    }

    /**
     * Units of $currency per 1 USD, using the given dollar quote. Null when it cannot be determined.
     */
    public function unitsPerUsd(Currency|string $currency, RateSource $source): ?float
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;

        if ($code === Currency::USD->value) {
            return 1.0;
        }

        $dollarRate = $this->getDollarRate($source);

        if ($code === Currency::ARS->value) {
            return $dollarRate;
        }

        $currencyRate = $this->getRate($code);

        if (! $dollarRate || ! $currencyRate) {
            return null;
        }

        return round($dollarRate / $currencyRate, 4);
    }

    /**
     * Converts an amount to USD. $rate is always "units of $currency per 1 USD"
     * (e.g. 1500 for ARS). Returns null when a non-USD amount has no valid rate:
     * amounts are never converted 1:1.
     */
    public static function toUsd(float|string|null $amount, Currency|string|null $currency, float|string|null $rate): ?float
    {
        $code = $currency instanceof Currency ? $currency->value : ($currency ?: Currency::USD->value);
        $amount = (float) ($amount ?? 0);

        if ($code === Currency::USD->value) {
            return round($amount, 2);
        }

        $rate = (float) ($rate ?? 0);

        if ($rate <= 0) {
            return null;
        }

        return round($amount / $rate, 2);
    }

    /**
     * Options for the rate source selector, showing today's value of each quoted dollar.
     *
     * @return array<string, string>
     */
    public function rateSourceOptions(): array
    {
        $options = [];

        foreach (RateSource::cases() as $source) {
            $rate = $this->getDollarRate($source);

            $options[$source->value] = $rate
                ? $source->getLabel().' ($ '.number_format($rate, 2, ',', '.').')'
                : $source->getLabel().($source === RateSource::Custom ? '' : ' (sin dato)');
        }

        return $options;
    }

    /**
     * Returns all stored data.
     *
     * @return array<string, mixed>
     */
    public function getAllData(): array
    {
        if (! Storage::exists($this->storagePath)) {
            $this->updateLocalRates();
        }

        if (! Storage::exists($this->storagePath)) {
            return [];
        }

        return json_decode((string) Storage::get($this->storagePath), true) ?? [];
    }
}
