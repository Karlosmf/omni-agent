<?php

namespace App\Filament\Admin\Resources\Quotations\Schemas;

use App\Enums\QuotationStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Detalles del Presupuesto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('quotation_number')
                            ->label('Número de Cotización')
                            ->disabled()
                            ->placeholder('Generado automáticamente'),
                        Select::make('status')
                            ->label('Estado')
                            ->options(QuotationStatus::class)
                            ->default(QuotationStatus::Draft)
                            ->required(),
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->default(request()->query('customer_id'))
                            ->disabled(fn () => request()->has('customer_id'))
                            ->dehydrated()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('Nombre'),
                                TextInput::make('email')->email()->label('Email'),
                                TextInput::make('phone')->label('Teléfono'),
                            ])
                            ->required(),
                        Select::make('lead_id')
                            ->label('Lead Relacionado')
                            ->relationship('lead', 'customer_name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('destination')
                            ->label('Destino Principal')
                            ->required(),
                        DatePicker::make('travel_date')
                            ->label('Fecha de Viaje Estimada'),
                        TextInput::make('nights')
                            ->label('Noches')
                            ->numeric(),
                        TextInput::make('passengers')
                            ->label('Cantidad de Pasajeros')
                            ->numeric()
                            ->default(2)
                            ->required(),
                        DatePicker::make('valid_until')
                            ->label('Válido hasta')
                            ->default(now()->addDays(7))
                            ->required(),
                    ]),

                \Filament\Schemas\Components\Section::make('Items del Presupuesto')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('items')
                            ->label('Servicios / Opciones')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(4)
                                    ->schema([
                                        TextInput::make('description')
                                            ->label('Descripción del Servicio')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('cost')
                                            ->label('Costo Estimado (USD)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => self::updateTotals($set, $get)),
                                        TextInput::make('sell')
                                            ->label('Precio Venta (USD)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => self::updateTotals($set, $get)),
                                    ]),
                            ])
                            ->createItemButtonLabel('Agregar Servicio')
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => self::updateTotals($set, $get)),
                    ]),

                \Filament\Schemas\Components\Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_cost')
                            ->label('Costo Total')
                            ->numeric()
                            ->prefix('USD')
                            ->readOnly(),
                        TextInput::make('total_sell')
                            ->label('Venta Total')
                            ->numeric()
                            ->prefix('USD')
                            ->readOnly(),
                        TextInput::make('profit')
                            ->label('Ganancia Estimada')
                            ->numeric()
                            ->prefix('USD')
                            ->readOnly(),
                    ]),

                \Filament\Schemas\Components\Section::make('Notas Adicionales')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas Internas o Condiciones')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function updateTotals(\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get): void
    {
        $items = $get('items') ?? [];
        $totalCost = 0;
        $totalSell = 0;

        foreach ($items as $item) {
            $totalCost += floatval($item['cost'] ?? 0);
            $totalSell += floatval($item['sell'] ?? 0);
        }

        $set('total_cost', number_format($totalCost, 2, '.', ''));
        $set('total_sell', number_format($totalSell, 2, '.', ''));
        $set('profit', number_format($totalSell - $totalCost, 2, '.', ''));
    }
}
