<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\Currency;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Cliente (Cuenta)')
                                    ->relationship('customer', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email})")
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->required(),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $data['role'] = \App\Enums\UserRole::Customer;
                                        $data['password'] = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12));

                                        return \App\Models\User::create($data)->id;
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $customer = \App\Models\User::find($state);
                                            if ($customer) {
                                                $set('holder_name', $customer->name);
                                            }
                                        }
                                    }),
                                TextInput::make('file_number')
                                    ->label('Nro. de Legajo')
                                    ->placeholder('Se generará automáticamente')
                                    ->disabledOn('create')
                                    ->unique(ignoreRecord: true),
                                Select::make('status')
                                    ->label('Estado')
                                    ->options(BookingStatus::class)
                                    ->default(BookingStatus::Borrador)
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('lead_id')
                                    ->label('Consulta de Origen')
                                    ->relationship('lead', 'customer_name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $lead = \App\Models\Lead::find($state);
                                            if ($lead) {
                                                $set('holder_name', $lead->customer_name);
                                                if ($lead->customer_id) {
                                                    $set('customer_id', $lead->customer_id);
                                                }
                                                $aiData = $lead->ai_data ?? [];
                                                if (! empty($aiData['destino'])) {
                                                    $set('destination', $aiData['destino']);
                                                }
                                            }
                                        }
                                    }),
                                TextInput::make('holder_name')
                                    ->label('Titular de la Reserva')
                                    ->required(),
                                TextInput::make('destination')
                                    ->label('Destino del Viaje'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                DatePicker::make('travel_date')
                                    ->label('Fecha de Salida'),
                                TextInput::make('nights')
                                    ->label('Noches')
                                    ->numeric(),
                                TextInput::make('passengers')
                                    ->label('Pasajeros')
                                    ->numeric()
                                    ->default(2),
                                DatePicker::make('valid_until')
                                    ->label('Vencimiento Presupuesto')
                                    ->default(now()->addDays(7)),
                            ]),
                        Textarea::make('internal_notes')
                            ->label('Observaciones Internas')
                            ->placeholder('Comentarios para uso interno (no se ven en el voucher/presupuesto)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Solo visible para el equipo administrativo.'),
                    ]),

                Section::make('Detalle de Servicios')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Servicios')
                            ->relationship('items')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('service_type_id')
                                            ->label('Tipo de Servicio')
                                            ->relationship('serviceType', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        TextInput::make('description')
                                            ->label('Descripción / Detalle')
                                            ->required()
                                            ->columnSpan(3),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        Select::make('supplier_id')
                                            ->label('Prestador / Proveedor')
                                            ->relationship('supplier', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Razón Social / Nombre')
                                                    ->required(),
                                                TextInput::make('category')
                                                    ->label('Categoría'),
                                            ]),

                                        Select::make('currency')
                                            ->label('Moneda')
                                            ->options(Currency::class)
                                            ->default(Currency::USD->value)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),

                                        TextInput::make('exchange_rate')
                                            ->label('Tipo de Cambio')
                                            ->numeric()
                                            ->default(1.00)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->visible(fn (Get $get) => self::getCurrencyLabel($get('currency')) !== 'USD')
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),

                                        TextInput::make('cost')
                                            ->label(fn (Get $get) => 'Costo neto ('.self::getCurrencyLabel($get('currency')).')')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),

                                        TextInput::make('sell')
                                            ->label(fn (Get $get) => 'Precio Venta ('.self::getCurrencyLabel($get('currency')).')')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotals($set, $get)),
                                    ]),
                            ])
                            ->columns(1)
                            ->itemLabel(function (array $state): ?string {
                                $typeId = $state['service_type_id'] ?? null;
                                $serviceType = \App\Models\ServiceType::find($typeId);
                                $label = $serviceType ? $serviceType->name : 'N/A';

                                return $label.': '.($state['description'] ?? '');
                            })
                            ->deleteAction(fn (Set $set, Get $get) => self::updateTotals($set, $get)),
                    ]),

                Section::make('Liquidación / Resumen')
                    ->columnSpanFull()
                    ->schema([
                        // ARS Summary
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_cost_ars_display')
                                    ->label('Costo Total (ARS)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('total_sell_ars_display')
                                    ->label('Precio Total (ARS)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('profit_ars_display')
                                    ->label('Rentabilidad (ARS)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100 font-bold text-success-600']),
                            ]),

                        // USD Summary
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_cost')
                                    ->label('Costo Total (USD)')
                                    ->numeric()
                                    ->prefix('USD')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('total_sell')
                                    ->label('Precio Total (USD)')
                                    ->numeric()
                                    ->prefix('USD')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('profit')
                                    ->label('Rentabilidad (USD)')
                                    ->numeric()
                                    ->prefix('USD')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100 font-bold text-success-600']),
                            ]),
                    ]),

                Section::make('Información Adicional')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas / Itinerario / Condiciones para el pasajero')
                            ->rows(3),
                    ]),
            ]);
    }

    protected static function getCurrencyLabel(mixed $currency): string
    {
        if ($currency instanceof Currency) {
            return $currency->value;
        }

        return (string) ($currency ?? 'USD');
    }

    public static function updateTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];

        $totalCostArs = 0;
        $totalSellArs = 0;

        $totalCostUsd = 0;
        $totalSellUsd = 0;

        foreach ($items as $item) {
            $currency = self::getCurrencyLabel($item['currency'] ?? 'USD');
            $rate = (float) ($item['exchange_rate'] ?? 1);
            $cost = (float) ($item['cost'] ?? 0);
            $sell = (float) ($item['sell'] ?? 0);

            if ($currency === Currency::USD->value) {
                $totalCostUsd += $cost;
                $totalSellUsd += $sell;
            } elseif ($currency === Currency::ARS->value) {
                $totalCostArs += $cost;
                $totalSellArs += $sell;
            } else {
                if ($rate > 0) {
                    $totalCostUsd += ($cost / $rate);
                    $totalSellUsd += ($sell / $rate);
                }
            }
        }

        // Set Display Values
        $set('total_cost_ars_display', number_format($totalCostArs, 2, '.', ''));
        $set('total_sell_ars_display', number_format($totalSellArs, 2, '.', ''));
        $set('profit_ars_display', number_format($totalSellArs - $totalCostArs, 2, '.', ''));

        // Set DB Values (USD bucket)
        $set('total_cost', number_format($totalCostUsd, 2, '.', ''));
        $set('total_sell', number_format($totalSellUsd, 2, '.', ''));
        $set('profit', number_format($totalSellUsd - $totalCostUsd, 2, '.', ''));
    }
}
