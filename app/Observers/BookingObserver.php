<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\Lead;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        BookingActivity::log(
            $booking,
            'created',
            "Presupuesto {$booking->file_number} creado",
        );

        $booking->lead?->syncStageWithBookings();
    }

    /**
     * A removed booking can take the inquiry back (for example, from "Reservada" to "Cotizada").
     */
    public function deleted(Booking $booking): void
    {
        $booking->lead?->syncStageWithBookings();
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged(['status', 'lead_id'])) {
            $booking->lead?->syncStageWithBookings();

            if ($booking->wasChanged('lead_id') && ($previous = Lead::find($booking->getOriginal('lead_id')))) {
                $previous->syncStageWithBookings();
            }
        }

        if ($booking->wasChanged('status')) {
            $oldStatus = $booking->getOriginal('status');
            $newStatus = $booking->status;

            BookingActivity::log(
                $booking,
                'status_changed',
                "Estado cambiado de {$oldStatus?->getLabel()} a {$newStatus->getLabel()}",
                [
                    'old' => $oldStatus?->value,
                    'new' => $newStatus->value,
                ],
            );
        }

        $trackedFields = ['total_sell', 'total_cost', 'destination', 'travel_date', 'valid_until', 'holder_name'];
        $changes = [];

        foreach ($trackedFields as $field) {
            if ($booking->wasChanged($field)) {
                $changes[$field] = [
                    'old' => $booking->getOriginal($field),
                    'new' => $booking->getAttribute($field),
                ];
            }
        }

        if (! empty($changes) && ! $booking->wasChanged('status')) {
            $fieldLabels = collect(array_keys($changes))->map(fn (string $f): string => match ($f) {
                'total_sell' => 'precio de venta',
                'total_cost' => 'costo',
                'destination' => 'destino',
                'travel_date' => 'fecha de viaje',
                'valid_until' => 'validez',
                'holder_name' => 'titular',
                default => $f,
            })->join(', ');

            BookingActivity::log(
                $booking,
                'updated',
                "Se actualizó: {$fieldLabels}",
                $changes,
            );
        }
    }
}
