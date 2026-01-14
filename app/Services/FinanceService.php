<?php

namespace App\Services;

class FinanceService
{
    /**
     * Convert an amount to USD using a fixed rate.
     */
    public function convertToUsd(float $amount, float $rate): float
    {
        if ($rate <= 0) {
            return $amount;
        }

        return round($amount / $rate, 2);
    }

    /**
     * Calculate profit in USD.
     */
    public function calculateProfit(float $costUsd, float $sellUsd): float
    {
        return round($sellUsd - $costUsd, 2);
    }
}
