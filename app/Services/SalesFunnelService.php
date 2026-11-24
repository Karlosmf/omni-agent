<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Lead;

/**
 * The all-time sales funnel: inquiries → quotes (bookings created) → sales (señados or emitidos).
 * One place for the counts and rates the dashboard widgets show.
 */
class SalesFunnelService
{
    /**
     * @return array{leads: int, quotes: int, sales: int, quote_rate: float, sale_rate: float, total_rate: float}
     */
    public function totals(): array
    {
        $leads = Lead::count();
        $quotes = Booking::count();
        $sales = Booking::confirmed()->count();

        return [
            'leads' => $leads,
            'quotes' => $quotes,
            'sales' => $sales,
            'quote_rate' => $this->rate($quotes, $leads),
            'sale_rate' => $this->rate($sales, $quotes),
            'total_rate' => $this->rate($sales, $leads),
        ];
    }

    /**
     * Confirmed sales in USD between two dates (by creation date): what was sold and what it cost.
     *
     * @return array{sell: float, cost: float}
     */
    public function usdSales(?\DateTimeInterface $from = null, ?\DateTimeInterface $until = null): array
    {
        $query = Booking::confirmed()
            ->where('currency', 'USD')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($until, fn ($query) => $query->where('created_at', '<', $until));

        return [
            'sell' => (float) (clone $query)->sum('total_sell'),
            'cost' => (float) $query->sum('total_cost'),
        ];
    }

    private function rate(int $part, int $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}
