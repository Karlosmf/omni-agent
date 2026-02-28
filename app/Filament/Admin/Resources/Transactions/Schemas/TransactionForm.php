<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\SupplierAccount;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Section::make('Calculadora de Neto / Impuestos')
                    ->description('Simula y descuenta impuestos automáticamente del monto final.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('tax_details.gross_amount')
                                    ->label('Monto Bruto')
                                    ->numeric()
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateNet($set, $get)),

                                TextInput::make('tax_details.tax_bank_percent')
                                    ->label('% Banco')
                                    ->numeric()
                                    ->default(1.2)
                                    ->live(onBlur: true)
                                    ->suffix('%')
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateNet($set, $get)),

                                TextInput::make('tax_details.tax_iibb_percent')
                                    ->label('% IIBB')
                                    ->numeric()
                                    ->default(3.5)
                                    ->live(onBlur: true)
                                    ->suffix('%')
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateNet($set, $get)),

                                TextInput::make('tax_details.platform_fee_percent')
                                    ->label('% Plataforma')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->suffix('%')
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateNet($set, $get)),
                            ]),
                    ]),

                Section::make('Detalles de la Transacción')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo de Transacción')
                                    ->options(TransactionType::class)
                                    ->required()
                                    ->live(),

                                Select::make('method')
                                    ->label('Método de Pago')
                                    ->options([
                                        'Efectivo' => 'Efectivo',
                                        'Transferencia' => 'Transferencia',
                                        'Tarjeta' => 'Tarjeta',
                                        'Mercado Pago' => 'Mercado Pago',
                                        'USDT' => 'USDT',
                                        'Cheque' => 'Cheque',
                                    ])
                                    ->required()
                                    ->live(),

                                Select::make('financial_account_id')
                                    ->label('Cuenta Financiera')
                                    ->relationship('financialAccount', 'name')
                                    // Hidden and optional if paying by Cash (Efectivo)
                                    ->required(fn (Get $get) => $get('method') !== 'Efectivo')
                                    ->visible(fn (Get $get) => $get('method') !== 'Efectivo')
                                    ->searchable()
                                    ->preload(),

                                Select::make('transaction_category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name', modifyQueryUsing: function ($query, Get $get) {
                                        $type = $get('type');
                                        if ($type === TransactionType::Cobro->value) {
                                            return $query->where('type', 'ingreso');
                                        }
                                        if ($type === TransactionType::Pago->value) {
                                            return $query->where('type', 'egreso');
                                        }

                                        return $query;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->columnSpan(fn (Get $get) => $get('method') === 'Efectivo' ? 2 : 1)
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        Select::make('type')->options(['ingreso' => 'Ingreso', 'egreso' => 'Egreso'])->required(),
                                    ]),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('booking_id')
                                    ->label('Expediente / Reserva')
                                    ->relationship('booking', 'file_number')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('type') === TransactionType::Cobro->value || $get('type') === TransactionType::Pago->value),

                                Select::make('supplier_id')
                                    ->label('Proveedor')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('type') === TransactionType::Pago->value)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('supplier_account_id', null)),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('supplier_account_id')
                                    ->label('Cuenta Bancaria del Proveedor')
                                    ->options(fn (Get $get) => SupplierAccount::where('supplier_id', $get('supplier_id'))->pluck('bank_name', 'id'))
                                    ->visible(fn (Get $get) => $get('type') === TransactionType::Pago->value && $get('supplier_id'))
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('bank_info_trigger', true)),

                                Placeholder::make('bank_info')
                                    ->label('Datos Bancarios')
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
                                            <div class='text-xs text-gray-600 bg-gray-50 p-2 rounded border'>
                                                <b>Banco:</b> {$account->bank_name} ({$account->currency})<br>
                                                <b>CBU:</b> {$account->cbu}<br>
                                                <b>Alias:</b> {$account->alias}
                                            </div>
                                        ");
                                    }),
                            ]),

                        TextInput::make('payer_name')
                            ->label('Pagador / Receptor (Manual)')
                            ->placeholder('Si no hay proveedor seleccionado')
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

                                Toggle::make('use_exchange_rate')
                                    ->label('Cotizar')
                                    ->inline(false)
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('exchange_rate')
                                    ->label('Cotización')
                                    ->numeric()
                                    ->default(1.00)
                                    ->live(onBlur: true)
                                    ->required(fn (Get $get) => $get('use_exchange_rate'))
                                    ->visible(fn (Get $get) => $get('use_exchange_rate'))
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUsdFixed($set, $get)),
                            ]),

                        TextInput::make('amount_usd_fixed')
                            ->label(fn (Get $get) => $get('use_exchange_rate') ? 'Total USD (Referencia)' : 'Total (Referencia)')
                            ->numeric()
                            ->readOnly()
                            ->prefix('$')
                            ->extraInputAttributes(['class' => 'bg-gray-100']),

                        Grid::make(1)
                            ->schema([
                                \Filament\Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->rows(2),

                                \Filament\Forms\Components\FileUpload::make('attachment_path')
                                    ->label('Comprobante')
                                    ->directory('transactions')
                                    ->columnSpanFull(),
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

        // Reset if toggle is off
        if (! $useExchangeRate) {
            // If toggle is off, reference total equals the amount (in local currency)
            $set('amount_usd_fixed', number_format($amount, 2, '.', ''));

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

    public static function calculateNet(Set $set, Get $get): void
    {
        $gross = (float) $get('tax_details.gross_amount');
        $bankPercent = (float) $get('tax_details.tax_bank_percent');
        $iibbPercent = (float) $get('tax_details.tax_iibb_percent');
        $platformPercent = (float) $get('tax_details.platform_fee_percent');

        // Allow calculation even if gross is 0 (to reset amount), but usually we want > 0
        // If percentages are edited, we recalculate amount.

        $taxAmount = $gross * (($bankPercent + $iibbPercent + $platformPercent) / 100);
        $net = $gross - $taxAmount;

        // Only update amount if we are using the calculator (e.g. gross is set)
        if ($get('tax_details.gross_amount') !== null && $get('tax_details.gross_amount') !== '') {
            $set('amount', number_format($net, 2, '.', ''));
            // Trigger dependency update manually
            self::updateUsdFixed($set, $get);
        }
    }
}
