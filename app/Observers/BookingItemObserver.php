<?php

namespace App\Observers;

use App\Models\BookingItem;
use App\Services\BookingFinancialService;

class BookingItemObserver
{
    protected BookingFinancialService $financialService;

    public function __construct(BookingFinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle the BookingItem "saved" event (created or updated).
     */
    public function saved(BookingItem $bookingItem): void
    {
        if ($bookingItem->booking) {
            $this->financialService->recalculateTotals($bookingItem->booking);
        }
    }

    /**
     * Handle the BookingItem "deleted" event.
     */
    public function deleted(BookingItem $bookingItem): void
    {
        if ($bookingItem->booking) {
            $this->financialService->recalculateTotals($bookingItem->booking);
        }
    }
}
