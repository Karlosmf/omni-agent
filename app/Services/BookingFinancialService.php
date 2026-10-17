<?php

namespace App\Services;

use App\Enums\Currency;
use App\Exceptions\MissingExchangeRateException;
use App\Models\Booking;

class BookingFinancialService
{
    /**
     * Recalculate and update the USD totals of a Booking from its items.
     *
     * Bookings are always carried in USD. Each non-USD item is converted with its own
     * stored rate (units of the item's currency per 1 USD) and is never assumed 1:1.
     */
    public function recalculateTotals(Booking $booking): void
    {
        $totals = $this->calculateTotals($booking->items()->get()->all());

        $booking->updateQuietly([
            'currency' => Currency::USD->value,
            'total_cost' => $totals['total_cost'],
            'total_sell' => $totals['total_sell'],
            'profit' => $totals['profit'],
        ]);
    }

    /**
     * Single source of truth for booking totals, also used by the form preview.
     *
     * @param  iterable<int, mixed>  $items  Models or arrays with currency, exchange_rate, cost and sell.
     * @return array{total_cost: float, total_sell: float, profit: float}
     *
     * @throws MissingExchangeRateException
     */
    public function calculateTotals(iterable $items): array
    {
        $totalCost = 0.0;
        $totalSell = 0.0;

        foreach ($items as $item) {
            $currency = data_get($item, 'currency');
            $rate = data_get($item, 'exchange_rate');

            $cost = CurrencyService::toUsd(data_get($item, 'cost'), $currency, $rate);
            $sell = CurrencyService::toUsd(data_get($item, 'sell'), $currency, $rate);

            if ($cost === null || $sell === null) {
                $code = $currency instanceof Currency ? $currency->value : (string) $currency;

                throw new MissingExchangeRateException("Falta la cotización de un servicio en {$code}.");
            }

            $totalCost += $cost;
            $totalSell += $sell;
        }

        return [
            'total_cost' => round($totalCost, 2),
            'total_sell' => round($totalSell, 2),
            'profit' => round($totalSell - $totalCost, 2),
        ];
    }
}
