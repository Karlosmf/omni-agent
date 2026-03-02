<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CurrencyService
{
    protected string $storagePath = 'currency_rates.json';

    /**
     * Fetches rates from Dolar API (Faster & more stable than scraping)
     */
    public function fetchRates(): ?array
    {
        try {
            $endpoints = [
                'USD' => 'https://dolarapi.com/v1/dolares/oficial',
                'EUR' => 'https://dolarapi.com/v1/cotizaciones/eur',
                'BRL' => 'https://dolarapi.com/v1/cotizaciones/brl',
            ];

            $results = [];
            $latestUpdate = null;

            foreach ($endpoints as $key => $url) {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $json = $response->json();

                    $name = $json['nombre'] ?? $key;
                    if ($key === 'USD' && $name === 'Oficial') {
                        $name = 'Dólar Oficial';
                    }

                    $results[$key] = [
                        'name' => $name,
                        'buy' => (float) ($json['compra'] ?? 0),
                        'sell' => (float) ($json['venta'] ?? 0),
                    ];

                    // Track the latest update across all fetched currencies
                    if (isset($json['fechaActualizacion'])) {
                        $date = Carbon::parse($json['fechaActualizacion']);
                        if (! $latestUpdate || $date->gt($latestUpdate)) {
                            $latestUpdate = $date;
                        }
                    }
                }
            }

            if (count($results) > 0) {
                return [
                    'currencies' => $results,
                    'updated_at' => ($latestUpdate ?? now())->toDateTimeString(),
                    'source' => 'Dolar API',
                ];
            }
        } catch (\Exception $e) {
            Log::error('CurrencyService: API Error: '.$e->getMessage());
        }

        return null; // Fallback to scraping if needed (omitted here to keep it clean)
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
     * Retrieves a stored exchange rate.
     */
    public function getRate(string $currency = 'USD', string $type = 'sell'): float
    {
        $data = $this->getAllData();

        return $data['currencies'][$currency][$type] ?? 1.0;
    }

    /**
     * Returns all stored data.
     */
    public function getAllData(): array
    {
        if (! Storage::exists($this->storagePath)) {
            $this->updateLocalRates();
        }

        $content = Storage::get($this->storagePath);

        return json_decode($content, true) ?? [];
    }
}
