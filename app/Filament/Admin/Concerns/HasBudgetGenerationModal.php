<?php

namespace App\Filament\Admin\Concerns;

use App\Enums\PriceBasis;
use App\Models\TravelPackage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Provides the reusable form schema for the "Generate Budget" modal.
 * Used in TravelPackagesTable, LeadsTable, EditLead, CreateCustomer, and EditCustomer.
 */
class HasBudgetGenerationModal
{
    /**
     * Returns the Filament form schema for the budget generation modal.
     * Pre-fills price and basis from the given TravelPackage when available.
     *
     * @return array<mixed>
     */
    public static function schema(?TravelPackage $package = null): array
    {
        return [
            Section::make('Datos del viaje')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('travel_date')
                                ->label('Fecha Estimada de Viaje')
                                ->default(now()->addMonths(3))
                                ->required(),

                            TextInput::make('adults')
                                ->label('Adultos')
                                ->numeric()
                                ->minValue(1)
                                ->default(2)
                                ->required()
                                ->live(),
                        ]),
                    Repeater::make('children')
                        ->label('Menores')
                        ->schema([
                            TextInput::make('age')
                                ->label('Edad')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->live(),
                        ])
                        ->addActionLabel('Agregar menor')
                        ->defaultItems(0)
                        ->columns(1)
                        ->live(),
                ]),

            Section::make('Precio')
                ->description('El precio y base se toman de la Idea de Viaje. Podés ajustarlos manualmente si el precio acordado es diferente.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('price_override')
                                ->label('Precio por unidad ($)')
                                ->numeric()
                                ->prefix('$')
                                ->default($package?->price_from)
                                ->required()
                                ->helperText('Precio por persona/unidad según la base elegida.'),

                            Select::make('basis_override')
                                ->label('Base del Precio')
                                ->options(PriceBasis::toOptions())
                                ->default($package?->price_basis?->value ?? PriceBasis::PorPersona->value)
                                ->required()
                                ->live()
                                ->helperText('Define cómo se multiplica el precio según los pasajeros.'),
                        ]),

                    Placeholder::make('price_preview')
                        ->label('Vista previa del total estimado')
                        ->content(function (Get $get) use ($package) {
                            $price = (float) ($get('price_override') ?? 0);
                            $adults = max(1, (int) ($get('adults') ?? 1));
                            $children = (array) ($get('children') ?? []);
                            $basis = PriceBasis::tryFrom($get('basis_override') ?? '') ?? PriceBasis::PorPersona;

                            $effectivePassengers = $adults + count($children);
                            $multiplier = $basis->multiplierFor($effectivePassengers);

                            $total = $price * $multiplier;

                            if ($package) {
                                $needsSingleSupplement = $effectivePassengers === 1
                                    && ! $basis->isFixed()
                                    && $basis !== PriceBasis::PorPersona;

                                if ($needsSingleSupplement) {
                                    $total += $package->singleSupplementFor($price);
                                }

                                if (in_array($basis, [PriceBasis::BaseTriple], true) && $package->triple_reduction_percent > 0) {
                                    $total -= $total * ((float) $package->triple_reduction_percent / 100);
                                }

                                // Children pricing (we invoke a protected method using Reflection or just duplicate logic for preview)
                                if (! empty($children) && ! empty($package->children_policies)) {
                                    $policies = collect($package->children_policies)->sortBy('max_age')->values();
                                    foreach ($children as $child) {
                                        $age = (int) ($child['age'] ?? 0);
                                        $policy = $policies->first(fn (array $p) => ($p['max_age'] ?? 999) >= $age);
                                        if (! $policy) {
                                            $total += $price;
                                        } else {
                                            $total += match ($policy['type'] ?? 'percent') {
                                                'free' => 0.0,
                                                'fixed' => (float) ($policy['value'] ?? 0),
                                                default => $price * ((float) ($policy['value'] ?? 100) / 100),
                                            };
                                        }
                                    }
                                }
                            }

                            return '$ '.number_format($total, 2, ',', '.');
                        }),
                ]),
        ];
    }
}
