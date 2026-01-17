<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\SupplierAccount;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                                Select::make('type')
                                    ->label('Tipo de Transacción')
                                    ->options(TransactionType::class)
                                    ->required()
                                    ->live(),

                                Select::make('booking_id')
                                    ->label('Expediente (Opcional)')
                                    ->relationship('booking', 'file_number')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),

                        // Proveedor y Cuenta (Solo para Pagos)
                        Grid::make(2)
                            ->schema([
                                Select::make('supplier_id')
                                    ->label('Proveedor (Destinatario)')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('type') === TransactionType::Pago->value)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('supplier_account_id', null)),

                                Select::make('supplier_account_id')
                                    ->label('Cuenta Bancaria')
                                    ->options(fn (Get $get) => SupplierAccount::where('supplier_id', $get('supplier_id'))->pluck('bank_name', 'id'))
                                    ->visible(fn (Get $get) => $get('type') === TransactionType::Pago->value && $get('supplier_id'))
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('bank_info_trigger', true)), // Dummy trigger
                            ]),

                        Placeholder::make('bank_info')
                            ->label('Datos de Transferencia')
                            ->visible(fn (Get $get) => $get('supplier_account_id'))
                            ->content(function (Get $get) {
                                $accountId = $get('supplier_account_id');
                                if (! $accountId) {
                                    return null;
                                }

                                $account = SupplierAccount::find($accountId);
                                if (! $account) {
                                    return null;
                                }

                                return new HtmlString("
                                    <div class='text-sm text-gray-600 bg-gray-50 p-3 rounded border'>
                                        <strong>Banco:</strong> {$account->bank_name} ({$account->currency})<br>
                                        <strong>CBU/CVU:</strong> {$account->cbu}<br>
                                        <strong>Alias:</strong> {$account->alias}<br>
                                        <strong>Cuenta:</strong> {$account->account_number}
                                    </div>
                                ");
                            }),

                        // Pagador / Beneficiario Manual (Solo si no hay proveedor o es cobro sin booking)
                        TextInput::make('payer_name')
                            ->label('Pagante / Beneficiario (Manual)')
                            ->placeholder('Nombre de quien paga o recibe')
                            ->visible(fn (Get $get) => ! $get('supplier_id'))
                            ->nullable(),

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
                                \Filament\Forms\Components\Toggle::make('use_exchange_rate')
                                    ->label('Registrar Tipo de Cambio')
                                    ->inline(false)
                                    ->default(true)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('exchange_rate')
                                    ->label('Tipo de Cambio')
                                    ->numeric()
                                    ->default(1.00)
                                    ->required(fn (Get $get) => $get('use_exchange_rate'))
                                    ->visible(fn (Get $get) => $get('use_exchange_rate'))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                                TextInput::make('amount_usd_fixed')
                                    ->label('Equivalente en USD (Congelado)')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('$')
                                    ->extraInputAttributes(['class' => 'bg-gray-100']),
                                TextInput::make('method')
                                    ->label('Método de Pago')
                                    ->datalist([
                                        'Efectivo',
                                        'Transferencia',
                                        'Tarjeta de Crédito',
                                        'Tarjeta de Débito',
                                        'Mercado Pago',
                                        'PayPal',
                                        'Zelle',
                                        'Cripto',
                                    ])
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

        if (! $useExchangeRate) {
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
