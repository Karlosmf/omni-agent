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
                                    ->required(),
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
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                TextInput::make('amount')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                TextInput::make('exchange_rate')
                                    ->label('Tipo de Cambio')
                                    ->numeric()
                                    ->default(1.00)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                            ]),
                        Grid::make(2)
                            ->schema([
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
