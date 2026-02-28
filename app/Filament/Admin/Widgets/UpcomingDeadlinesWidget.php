<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\Widget;

class UpcomingDeadlinesWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.upcoming-deadlines-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getUpcomingTrips(): array
    {
        return Booking::whereDate('travel_date', '>=', now())
            ->whereDate('travel_date', '<=', now()->addDays(30))
            ->orderBy('travel_date')
            ->limit(5)
            ->get()
            ->map(fn ($booking) => [
                'id' => $booking->id,
                'file_number' => $booking->file_number,
                'holder_name' => $booking->holder_name,
                'travel_date' => $booking->travel_date,
                'days_until' => now()->diffInDays($booking->travel_date, false),
                'status' => $booking->status,
            ])
            ->toArray();
    }

    public function getPendingPayments(): array
    {
        // For now, return empty. Will implement when supplier payment due dates are added
        return [];
    }
}
