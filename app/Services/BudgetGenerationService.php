<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PriceBasis;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ServiceType;
use App\Models\TravelPackage;
use App\Models\User;

class BudgetGenerationService
{
    /**
     * Creates a new cloned Budget (Booking) for a Customer based on a TravelPackage.
     *
     * Pricing rules applied in order:
     *
     * 1. TEMPORADA: price_from is resolved from the matching season for travel_date.
     * 2. OVERRIDE: priceOverride / basisOverride replace the resolved defaults when given.
     * 3. BASIS: total = price × multiplierFor(passengers) using the effective basis.
     * 4. SUPLEMENTO SINGLE: when passengers=1 and the basis is not 'por_persona' or 'precio_fijo',
     *    an extra single supplement is added on top.
     * 5. REDUCCIÓN TRIPLE: when the effective basis is 'base_triple', a percentage discount
     *    is applied per billed slot relative to the base_doble rate.
     * 6. MENORES: adults pay full price; children are priced per the children_policies bracket.
     *
     * @param  float|null  $priceOverride  Replaces price_from (manual agent override).
     * @param  PriceBasis|null  $basisOverride  Replaces price_basis (manual agent override).
     * @param  int  $adults  Number of adults (full-price passengers).
     * @param  array<array{age: int}>  $children  Children with their ages.
     */
    public function clonePackageToBudget(
        TravelPackage $travelPackage,
        User $customer,
        ?int $leadId = null,
        ?string $travelDate = null,
        ?int $passengers = null,
        ?float $priceOverride = null,
        ?PriceBasis $basisOverride = null,
        int $adults = 0,
        array $children = [],
    ): Booking {
        $effectivePassengers = $passengers ?? max(1, $adults + count($children));
        if ($adults === 0) {
            $adults = $effectivePassengers; // backwards compat: treat all as adults
        }

        // 1. Season-aware price resolution
        $seasonPrice = $travelPackage->priceForDate($travelDate);

        // 2. Apply manual overrides if supplied
        $activeBasis = $basisOverride ?? $travelPackage->price_basis ?? PriceBasis::PorPersona;
        $activePrice = $priceOverride ?? $seasonPrice;

        // 3. Base occupancy calculation
        $multiplier = $activeBasis->multiplierFor($effectivePassengers);
        $totalSell = $activePrice * $multiplier;

        // 4. Single supplement (only when 1 passenger + basis requires a room)
        $singleSupplement = 0.0;
        $needsSingleSupplement = $effectivePassengers === 1
            && ! $activeBasis->isFixed()
            && $activeBasis !== PriceBasis::PorPersona;

        if ($needsSingleSupplement) {
            $singleSupplement = $travelPackage->singleSupplementFor($activePrice);
            $totalSell += $singleSupplement;
        }

        // 5. Triple reduction: discount applied when using base_triple
        $tripleReduction = 0.0;
        if (
            in_array($activeBasis, [PriceBasis::BaseTriple], true)
            && $travelPackage->triple_reduction_percent > 0
        ) {
            $tripleReduction = $totalSell * ((float) $travelPackage->triple_reduction_percent / 100);
            $totalSell -= $tripleReduction;
        }

        // 6. Children pricing
        $childrenTotal = $this->calculateChildrenTotal($travelPackage, $activePrice, $children);
        $totalSell += $childrenTotal;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'lead_id' => $leadId,
            'holder_name' => $customer->name,
            'destination' => $travelPackage->destination,
            'nights' => $travelPackage->nights,
            'passengers' => $effectivePassengers,
            'currency' => $travelPackage->currency,
            'exchange_rate' => 1.00,
            'total_cost' => 0,
            'total_sell' => $totalSell,
            'profit' => $totalSell,
            'status' => BookingStatus::Borrador,
            'travel_date' => $travelDate ?? now()->addMonths(3),
            'valid_until' => now()->addDays(7),
            'internal_notes' => $this->buildInternalNotes($travelPackage, $activeBasis, $activePrice, $singleSupplement, $tripleReduction, $childrenTotal),
            'notes' => $travelPackage->description."\n".$travelPackage->summary,
        ]);

        $hasDetailedServices = is_array($travelPackage->services) && count($travelPackage->services) > 0;

        if ($hasDetailedServices) {
            foreach ($travelPackage->services as $service) {
                $serviceBasis = PriceBasis::tryFrom($service['price_basis'] ?? '') ?? $activeBasis;
                $serviceMultiplier = $serviceBasis->multiplierFor($effectivePassengers);

                $sellRatio = $priceOverride !== null && (float) $travelPackage->price_from > 0
                    ? $priceOverride / (float) $travelPackage->price_from
                    : 1.0;

                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type_id' => $service['service_type_id'] ?? ServiceType::where('key', 'other')->value('id') ?? 1,
                    'description' => $service['description'],
                    'supplier_id' => $service['supplier_id'] ?? null,
                    'currency' => $service['currency'] ?? $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => (float) ($service['cost'] ?? 0) * $serviceMultiplier,
                    'sell' => (float) ($service['sell'] ?? 0) * $serviceMultiplier * $sellRatio,
                ]);
            }

            // Supplement / reduction / children as separate line items for transparency
            if ($singleSupplement > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type_id' => ServiceType::where('key', 'other')->value('id') ?? 1,
                    'description' => 'Suplemento single',
                    'currency' => $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => 0,
                    'sell' => $singleSupplement,
                ]);
            }

            if ($tripleReduction > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type_id' => ServiceType::where('key', 'other')->value('id') ?? 1,
                    'description' => 'Reducción base triple ('.$travelPackage->triple_reduction_percent.'%)',
                    'currency' => $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => 0,
                    'sell' => -$tripleReduction,
                ]);
            }

            if ($childrenTotal > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_type_id' => ServiceType::where('key', 'other')->value('id') ?? 1,
                    'description' => 'Tarifas menores ('.count($children).' niño/s)',
                    'currency' => $travelPackage->currency,
                    'exchange_rate' => 1.00,
                    'cost' => 0,
                    'sell' => $childrenTotal,
                ]);
            }
        }

        // Fallback: no detailed services → one summary line
        if (! $hasDetailedServices) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'service_type_id' => ServiceType::where('key', 'other')->value('id') ?? 1,
                'description' => 'Servicios integrales del paquete: '.$travelPackage->title,
                'currency' => $travelPackage->currency,
                'exchange_rate' => 1.00,
                'cost' => 0,
                'sell' => $totalSell,
            ]);
        }

        $this->recalculateTotals($booking, useItemSumDirectly: $hasDetailedServices);

        return $booking;
    }

    /**
     * Calculates the total sell price for children based on children_policies.
     *
     * Policy lookup: finds the first policy whose max_age >= child's age.
     * If no policy matches, the child pays the adult price.
     *
     * Policy types:
     *   - 'free'    → child pays 0
     *   - 'percent' → child pays (value% of baseUnitPrice)
     *   - 'fixed'   → child pays the fixed amount
     *
     * @param  array<array{age: int}>  $children
     */
    protected function calculateChildrenTotal(TravelPackage $travelPackage, float $baseUnitPrice, array $children): float
    {
        if (empty($children) || empty($travelPackage->children_policies)) {
            return 0.0;
        }

        $policies = collect($travelPackage->children_policies)
            ->sortBy('max_age')
            ->values();

        $total = 0.0;

        foreach ($children as $child) {
            $age = (int) ($child['age'] ?? 0);

            $policy = $policies->first(fn (array $p) => ($p['max_age'] ?? 999) >= $age);

            if (! $policy) {
                $total += $baseUnitPrice; // no matching bracket → adult price

                continue;
            }

            $total += match ($policy['type'] ?? 'percent') {
                'free' => 0.0,
                'fixed' => (float) ($policy['value'] ?? 0),
                default => $baseUnitPrice * ((float) ($policy['value'] ?? 100) / 100),
            };
        }

        return $total;
    }

    /**
     * Builds a descriptive internal note summarising the pricing rules applied.
     */
    protected function buildInternalNotes(
        TravelPackage $travelPackage,
        PriceBasis $basis,
        float $price,
        float $singleSupplement,
        float $tripleReduction,
        float $childrenTotal,
    ): string {
        $notes = 'Presupuesto generado a partir de Idea de Viaje: '.$travelPackage->title;
        $notes .= "\nBase: ".$basis->label().' | Precio unitario: $'.number_format($price, 2);

        if ($singleSupplement > 0) {
            $notes .= ' | Suplemento single: +$'.number_format($singleSupplement, 2);
        }
        if ($tripleReduction > 0) {
            $notes .= ' | Reducción triple: -$'.number_format($tripleReduction, 2);
        }
        if ($childrenTotal > 0) {
            $notes .= ' | Menores: +$'.number_format($childrenTotal, 2);
        }

        return $notes;
    }

    /**
     * @param  bool  $useItemSumDirectly  When true, the item sum is used as-is.
     *                                    When false, the booking's indicative total_sell
     *                                    acts as a floor (prevents reducing below the
     *                                    package's indicative price).
     */
    protected function recalculateTotals(Booking $booking, bool $useItemSumDirectly = false): void
    {
        $items = $booking->items;
        $cost = $items->sum('cost');
        $sell = $items->sum('sell');

        $booking->update([
            'total_cost' => $cost,
            'total_sell' => $useItemSumDirectly ? $sell : max($booking->total_sell, $sell),
            'profit' => ($useItemSumDirectly ? $sell : max($booking->total_sell, $sell)) - $cost,
        ]);
    }
}
