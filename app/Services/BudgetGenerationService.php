<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ServiceType;
use App\Models\TravelPackage;
use App\Models\User;

class BudgetGenerationService
{
    /**
     * Creates a new cloned Budget (Booking) for a Customer based on a TravelPackage.
     */
    public function clonePackageToBudget(TravelPackage $travelPackage, User $customer, ?int $leadId = null, ?string $travelDate = null, ?int $passengers = null): Booking
    {
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'lead_id' => $leadId,
            'holder_name' => $customer->name,
            'destination' => $travelPackage->destination,
            'nights' => $travelPackage->nights,
            'passengers' => $passengers ?? 2,
            'currency' => $travelPackage->currency,
            'exchange_rate' => 1.00,
            'total_cost' => 0,
            'total_sell' => $travelPackage->price_from,
            'profit' => $travelPackage->price_from,
            'status' => BookingStatus::Borrador,
            'travel_date' => $travelDate ?? now()->addMonths(3),
            'valid_until' => now()->addDays(7), // Quotation valid for 7 days
            'internal_notes' => 'Presupuesto generado a partir de Idea de Viaje: '.$travelPackage->title,
            'notes' => $travelPackage->description."\n".$travelPackage->summary,
        ]);

        $hasDetailedServices = is_array($travelPackage->services) && count($travelPackage->services) > 0;

        if ($hasDetailedServices) {
            foreach ($travelPackage->services as $service) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type_id' => $service['service_type_id'] ?? ServiceType::where('key', 'other')->value('id') ?? 1,
                    'description' => $service['description'],
                    'supplier_id' => $service['supplier_id'] ?? null,
                    'currency' => $service['currency'] ?? $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => $service['cost'] ?? 0,
                    'sell' => $service['sell'] ?? 0,
                ]);
            }
        }

        // Fallback: If no detailed services exist
        if (! $hasDetailedServices) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'service_type_id' => ServiceType::where('key', 'other')->value('id') ?? 1,
                'description' => 'Servicios integrales del paquete: '.$travelPackage->title,
                'currency' => $travelPackage->currency,
                'exchange_rate' => 1.00,
                'cost' => 0,
                'sell' => $travelPackage->price_from,
            ]);
        }

        // Recalculate totals based on all created items
        $this->recalculateTotals($booking);

        return $booking;
    }

    protected function recalculateTotals(Booking $booking): void
    {
        $items = $booking->items;
        $cost = $items->sum('cost');
        $sell = $items->sum('sell');

        $booking->update([
            'total_cost' => $cost,
            'total_sell' => max($booking->total_sell, $sell),
            'profit' => max($booking->total_sell, $sell) - $cost,
        ]);
    }
}
