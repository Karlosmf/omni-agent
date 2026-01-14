<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\ServiceType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Cliente (Cuenta)')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->required(),
                                    ])
                                    ->required(),
                                TextInput::make('file_number')
                                    ->label('Nro Expediente')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('status')
                                    ->label('Estado')
                                    ->options(BookingStatus::class)
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('lead_id')
                                    ->label('Lead Origen')
                                    ->relationship('lead', 'customer_name')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('holder_name')
                                    ->label('Nombre del Pasajero Principal')
                                    ->required(),
                                DatePicker::make('travel_date')
                                    ->label('Fecha de Viaje')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Servicios / Items')
                    ->schema([
                        Repeater::make('items')
                            ->label('Servicios Detallados')
                            ->relationship('items')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('type')
                                            ->label('Tipo')
                                            ->options(ServiceType::class)
                                            ->required(),
                                        TextInput::make('description')
                                            ->label('Descripción')
                                            ->required()
                                            ->columnSpan(2),
                                        Select::make('supplier_id')
                                            ->label('Proveedor')
                                            ->relationship('supplier', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Nombre Proveedor')
                                                    ->required(),
                                                TextInput::make('category')
                                                    ->label('Categoría'),
                                            ])
                                            ->required(),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('cost_usd')
                                            ->label('Costo (USD)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),
                                        TextInput::make('sell_usd')
                                            ->label('Venta (USD)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),
                                    ]),
                            ])
                            ->columns(1)
                            ->itemLabel(function (array $state): ?string {
                                $type = $state['type'] ?? null;
                                $label = $type instanceof ServiceType ? $type->getLabel() : $type;

                                return $label.': '.($state['description'] ?? '');
                            })
                            ->deleteAction(fn (Set $set, Get $get) => self::updateTotals($set, $get)),
                    ]),

                Section::make('Resumen Financiero')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_cost_usd')
                                    ->label('Costo Total (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('total_sell_usd')
                                    ->label('Venta Total (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('profit_usd')
                                    ->label('Ganancia Total (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100 font-bold text-success-600']),
                            ]),
                    ]),
            ]);
    }

    public static function updateTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];
        $totalCost = 0;
        $totalSell = 0;

        foreach ($items as $item) {
            $totalCost += (float) ($item['cost_usd'] ?? 0);
            $totalSell += (float) ($item['sell_usd'] ?? 0);
        }

        $set('total_cost_usd', number_format($totalCost, 2, '.', ''));
        $set('total_sell_usd', number_format($totalSell, 2, '.', ''));
        $set('profit_usd', number_format($totalSell - $totalCost, 2, '.', ''));
    }
}
