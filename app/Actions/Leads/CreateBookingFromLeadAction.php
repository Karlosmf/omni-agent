<?php

namespace App\Actions\Leads;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\PriceBasis;
use App\Models\Booking;
use App\Models\Lead;
use App\Services\BudgetGenerationService;

/**
 * Creates the booking ("expediente") of a lead from the reviewed "Crear Expediente" form and closes the lead.
 */
class CreateBookingFromLeadAction
{
    public function __construct(private BudgetGenerationService $budgets) {}

    /**
     * @param  array<string, mixed>  $data  Form values: travel_date, adults, children, price_override, basis_override, holder_name, destination, nights, notes.
     */
    public function execute(Lead $lead, array $data): Booking
    {
        $children = (array) ($data['children'] ?? []);

        if ($lead->travelPackage && $lead->customer) {
            $booking = $this->budgets->clonePackageToBudget(
                $lead->travelPackage,
                $lead->customer,
                $lead->id,
                $data['travel_date'] ?? null,
                null,
                isset($data['price_override']) ? (float) $data['price_override'] : null,
                PriceBasis::tryFrom($data['basis_override'] ?? '') ?: null,
                (int) ($data['adults'] ?? 1),
                $children,
            );

            if (filled($data['holder_name'] ?? null) || filled($data['notes'] ?? null)) {
                $booking->update(array_filter([
                    'holder_name' => $data['holder_name'] ?? null,
                    'internal_notes' => $data['notes'] ?? null,
                ]));
            }
        } else {
            $booking = Booking::create([
                'lead_id' => $lead->id,
                'customer_id' => $lead->customer_id,
                'holder_name' => filled($data['holder_name'] ?? null) ? $data['holder_name'] : ($lead->customer?->name ?? 'A definir'),
                'destination' => filled($data['destination'] ?? null) ? $data['destination'] : ($lead->ai_data['destino'] ?? null),
                'nights' => filled($data['nights'] ?? null) ? (int) $data['nights'] : null,
                'passengers' => (int) (($data['adults'] ?? 1) + count($children)),
                'status' => BookingStatus::Borrador,
                'travel_date' => $data['travel_date'] ?? now()->addMonths(1),
                'valid_until' => now()->addDays(7),
                'internal_notes' => $data['notes'] ?? null,
            ]);
        }

        $lead->update(['status' => LeadStatus::Closed]);

        return $booking;
    }
}
