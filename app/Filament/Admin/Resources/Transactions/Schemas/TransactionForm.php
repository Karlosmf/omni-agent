<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Section::make('Detalles de la Transacción')
                        ->schema([
                                Grid::make(2)
                                    ->schema([
                                            Select::make('booking_id')
                                                ->label('Expediente Relacionado')
                                                ->relationship('booking', 'file_number')
                                                ->searchable()
                                                ->preload()
                                                ->live(), // Make it live to control payer_name visibility if needed
                                            TextInput::make('payer_name')
                                                ->label('Pagante / Beneficiario')
                                                ->placeholder('Nombre del pagante o asunto del gasto')
                                                // Visible if no booking is selected, or always allowed? User said "the option of related file only in case of a trip".
                                                // But general expenses might have a payer too?
                                                // "que no sea una opcion obligatoria sino que se pueda tipear el nombre del pagante o el asunto del gasto"
                                                // I'll make it always visible but perhaps highlighting it's alternative?
                                                // Actually, if booking IS select, we might infer payer from booking, but let's keep it simple: always visible, optional.
                                                ->nullable(),
                                            Select::make('type')
                                                ->label('Tipo de Transacción')
                                                ->options(TransactionType::class)
                                                ->required(),
                                        ]),
                                Grid::make(3)
                                    ->schema([
                                            Select::make('currency')
                                                ->label('Moneda')
                                                ->options(Currency::class)
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                            TextInput::make('amount')
                                                ->label('Monto')
                                                ->numeric()
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                            \Filament\Forms\Components\Toggle::make('use_exchange_rate')
                                                ->label('Registrar Tipo de Cambio')
                                                ->inline(false)
                                                ->default(true)
                                                ->live()
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                        ]),
                                Grid::make(2)
                                    ->schema([
                                            TextInput::make('exchange_rate')
                                                ->label('Tipo de Cambio')
                                                ->numeric()
                                                ->default(1.00)
                                                ->required(fn(Get $get) => $get('use_exchange_rate'))
                                                ->visible(fn(Get $get) => $get('use_exchange_rate'))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                            TextInput::make('amount_usd_fixed')
                                                ->label('Equivalente en USD (Congelado)')
                                                ->numeric()
                                                ->readOnly()
                                                ->prefix('$')
                                                ->extraInputAttributes(['class' => 'bg-gray-100']),
                                            TextInput::make('method')
                                                ->placeholder('ej: Efectivo, Transferencia, etc.')
                                                ->required(),
                                        ]),
                                Grid::make(1)
                                    ->schema([
                                            \Filament\Forms\Components\Textarea::make('notes')
                                                ->label('Notas / Concepto')
                                                ->placeholder('Detalles adicionales del pago o cobro...')
                                                ->rows(3),
                                        ]),
                            ]),
                ]);
    }

    public static function updateUsdFixed(Set $set, Get $get): void
    {
        $amount = (float) ($get('amount') ?? 0);
        $rate = (float) ($get('exchange_rate') ?? 1);
        $currency = $get('currency');
        $useExchangeRate = (bool) $get('use_exchange_rate');

        if (!$useExchangeRate) {
            $set('amount_usd_fixed', null);
            return;
        }

        if ($currency === Currency::USD->value) {
            $set('exchange_rate', 1.00);
            $set('amount_usd_fixed', number_format($amount, 2, '.', ''));
        } else {
            if ($rate > 0) {
                $set('amount_usd_fixed', number_format($amount / $rate, 2, '.', ''));
            }
        }
    }
}
