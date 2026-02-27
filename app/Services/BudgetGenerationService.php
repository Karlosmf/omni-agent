<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\TravelPackage;
use App\Enums\BookingStatus;
use App\Enums\ServiceType;

class BudgetGenerationService
{
    /**
     * Creates a new cloned Budget (Booking) for a Customer based on a TravelPackage.
     *
     * @param TravelPackage $travelPackage
     * @param Customer $customer
     * @param int|null $leadId
     * @return Booking
     */
    public function clonePackageToBudget(TravelPackage $travelPackage, Customer $customer, ?int $leadId = null): Booking
    {
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'lead_id' => $leadId,
            'holder_name' => $customer->name,
            'destination' => $travelPackage->destination,
            'nights' => $travelPackage->nights,
            'passengers' => 2, // Default
            'currency' => $travelPackage->currency,
            'exchange_rate' => 1.00,
            'total_cost' => 0,
            'total_sell' => $travelPackage->price_from,
            'profit' => $travelPackage->price_from,
            'status' => BookingStatus::Borrador,
            'travel_date' => now()->addMonths(3), // Default future date
            'valid_until' => now()->addDays(7), // Quotation valid for 7 days
            'internal_notes' => 'Presupuesto generado a partir de Idea de Viaje: ' . $travelPackage->title,
            'notes' => $travelPackage->description . "\n" . $travelPackage->summary,
        ]);

        // If itinerary exists, create booking items for them
        if (is_array($travelPackage->itinerary) && count($travelPackage->itinerary) > 0) {
            foreach ($travelPackage->itinerary as $day) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type' => ServiceType::Other,
                    'description' => "Día {$day['day']}: {$day['title']} - {$day['description']}",
                    'currency' => $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => 0, // Placeholder
                    'sell' => 0, // Placeholder, total is in main booking
                ]);
            }
        } else {
            // Create a general package item so the budget isn't completely empty
            BookingItem::create([
                'booking_id' => $booking->id,
                'service_type' => ServiceType::Other,
                'description' => 'Servicios integrales del paquete: ' . $travelPackage->title,
                'currency' => $travelPackage->currency,
                'exchange_rate' => 1.00,
                'cost' => 0,
                'sell' => $travelPackage->price_from,
            ]);
        }

        // Recalculate totals if we added a package item with sell price
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
