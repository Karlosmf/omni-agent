<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Booking;

class BookingFinancialService
{
    /**
     * Recalculate and update the totals for a specific Booking based on items and transactions.
     */
    public function recalculateTotals(Booking $booking): void
    {
        $totalCost = 0;
        $totalSell = 0;

        // Asumimos que la moneda "base" del legajo es la que dice $booking->currency (suele ser USD)
        // Y usamos $booking->exchange_rate para conversiones internas.
        $bookingRate = (float) ($booking->exchange_rate > 0 ? $booking->exchange_rate : 1);

        foreach ($booking->items as $item) {
            $currency = $item->currency instanceof Currency ? $item->currency->value : (string) $item->currency;
            $rate = (float) ($item->exchange_rate > 0 ? $item->exchange_rate : 1);
            $cost = (float) ($item->cost ?? 0);
            $sell = (float) ($item->sell ?? 0);

            if ($currency === Currency::USD->value) {
                $totalCost += $cost;
                $totalSell += $sell;
            } elseif ($currency === Currency::ARS->value) {
                // BUGFIX: Convertimos los ARS a USD usando el rate de la reserva para tener una métrica unificada
                $totalCost += ($cost / $bookingRate);
                $totalSell += ($sell / $bookingRate);
            } else {
                // Otras monedas (ej. EUR) usando el rate cargado en el item específico
                $totalCost += ($cost / $rate);
                $totalSell += ($sell / $rate);
            }
        }

        // Actualizamos sin disparar eventos en bucle
        $booking->updateQuietly([
            'total_cost' => round($totalCost, 2),
            'total_sell' => round($totalSell, 2),
            'profit' => round($totalSell - $totalCost, 2),
        ]);
    }
}
