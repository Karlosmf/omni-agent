<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Transaction;
use App\Services\InstallmentService;

class TransactionObserver
{
    public function __construct(private InstallmentService $installments) {}

    public function saved(Transaction $transaction): void
    {
        $this->syncBookings($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->syncBookings($transaction);
    }

    private function syncBookings(Transaction $transaction): void
    {
        $bookingIds = array_filter(array_unique([$transaction->booking_id, $transaction->getOriginal('booking_id')]));

        foreach ($bookingIds as $id) {
            if ($booking = Booking::find($id)) {
                $this->installments->sync($booking);
            }
        }
    }
}
