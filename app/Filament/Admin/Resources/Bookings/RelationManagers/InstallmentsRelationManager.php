<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use App\Enums\Currency;
use App\Enums\RateSource;
use App\Enums\TransactionType;
use App\Models\PaymentInstallment;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\CurrencyService;
use App\Services\InstallmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Plan de Pagos';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('label')->label('Cuota'),
                TextColumn::make('due_date')->label('Vence')->date('d/m/Y'),
                TextColumn::make('amount')->label('Monto (USD)')->money('USD'),
                TextColumn::make('paid_amount')->label('Cobrado (USD)')->money('USD'),
                TextColumn::make('remaining')
                    ->label('Saldo (USD)')
                    ->state(fn (PaymentInstallment $record) => $record->remaining())
                    ->money('USD'),
                TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->headerActions([
                Action::make('generatePlan')
                    ->label('Generar plan')
                    ->icon('heroicon-o-calendar-days')
                    ->modalHeading('Generar plan de pagos')
                    ->modalDescription('Se calcula sobre el total de venta del expediente, en USD. Reemplaza el plan actual si todavía no tiene cobros.')
                    ->schema([
                        TextInput::make('deposit_percent')->label('Seña (%)')->numeric()->default(30)->minValue(0)->maxValue(100)->required(),
                        TextInput::make('installments')->label('Cantidad de cuotas del saldo')->numeric()->integer()->default(3)->minValue(1)->maxValue(24)->required(),
                        DatePicker::make('first_due_date')->label('Primer vencimiento de cuota')->default(now()->addMonth())->required(),
                        Select::make('frequency')->label('Frecuencia')->options(InstallmentService::FREQUENCIES)->default('monthly')->required(),
                    ])
                    ->action(function (array $data, InstallmentService $service) {
                        try {
                            $service->generate(
                                $this->getOwnerRecord(),
                                (float) $data['deposit_percent'],
                                (int) $data['installments'],
                                Carbon::parse($data['first_due_date']),
                                $data['frequency'],
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('No se pudo generar el plan')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Plan de pagos generado')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('registerPayment')
                    ->label('Registrar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (PaymentInstallment $record) => $record->status->isOpen())
                    ->modalHeading(fn (PaymentInstallment $record) => "Registrar pago — {$record->label}")
                    ->fillForm(fn (PaymentInstallment $record) => [
                        'currency' => 'USD',
                        'amount' => $record->remaining(),
                        'method' => 'Transferencia',
                        'date' => now()->toDateString(),
                    ])
                    ->schema([
                        Select::make('currency')
                            ->label('Moneda')
                            ->options(['USD' => 'Dólares (USD)', 'ARS' => 'Pesos Argentinos (ARS)'])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, PaymentInstallment $record) {
                                if ($get('currency') === 'ARS') {
                                    $source = get_agency_settings()?->default_rate_source ?? RateSource::Oficial;
                                    $rate = app(CurrencyService::class)->unitsPerUsd(Currency::ARS, $source);
                                    $set('rate_source', $source->value);
                                    $set('exchange_rate', $rate);
                                    $set('amount', $rate ? round($record->remaining() * $rate, 2) : null);
                                } else {
                                    $set('exchange_rate', null);
                                    $set('rate_source', null);
                                    $set('amount', $record->remaining());
                                }
                            }),
                        TextInput::make('amount')->label('Monto')->numeric()->required(),
                        Select::make('rate_source')
                            ->label('Cotización del día')
                            ->options(fn () => app(CurrencyService::class)->rateSourceOptions())
                            ->visible(fn (Get $get) => $get('currency') === 'ARS')
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $source = RateSource::tryFrom((string) $state);
                                if ($source && $source !== RateSource::Custom) {
                                    $set('exchange_rate', app(CurrencyService::class)->unitsPerUsd(Currency::ARS, $source));
                                }
                            }),
                        TextInput::make('exchange_rate')
                            ->label('ARS por 1 USD')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required(fn (Get $get) => $get('currency') === 'ARS')
                            ->visible(fn (Get $get) => $get('currency') === 'ARS')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set) => $set('rate_source', RateSource::Custom->value)),
                        Select::make('method')
                            ->label('Método de pago')
                            ->options(['Efectivo' => 'Efectivo', 'Transferencia' => 'Transferencia', 'Tarjeta' => 'Tarjeta', 'Mercado Pago' => 'Mercado Pago', 'USDT' => 'USDT', 'Cheque' => 'Cheque'])
                            ->required(),
                        DatePicker::make('date')->label('Fecha del cobro')->required(),
                    ])
                    ->action(function (array $data, PaymentInstallment $record) {
                        Transaction::create([
                            'booking_id' => $record->booking_id,
                            'installment_id' => $record->id,
                            'transaction_category_id' => TransactionCategory::firstOrCreate(
                                ['name' => 'Pago de Pasajero'],
                                ['type' => 'ingreso', 'is_system' => true],
                            )->id,
                            'type' => TransactionType::Cobro,
                            'currency' => $data['currency'],
                            'amount' => $data['amount'],
                            'exchange_rate' => $data['exchange_rate'] ?? null,
                            'rate_source' => $data['rate_source'] ?? null,
                            'method' => $data['method'],
                            'date' => $data['date'],
                            'notes' => "Pago de {$record->label}",
                        ]);

                        Notification::make()->title('Pago registrado')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Todavía no hay un plan de pagos')
            ->emptyStateDescription('Usá "Generar plan" para crear la seña y las cuotas.');
    }
}
