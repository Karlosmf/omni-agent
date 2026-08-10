<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\CurrencyService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
                                        $data['role'] = UserRole::Customer;
                                        $data['password'] = Str::random(12);

                                        return User::forceCreate($data)->id;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $customer = User::find($state);
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
                        Grid::make(2)
                            ->schema([
                                Select::make('agent_id')
                                    ->label('Agente Asignado')
                                    ->relationship('agent', 'name', fn ($query) => $query->where('role', UserRole::Sales)->orWhere('role', UserRole::Admin))
                                    ->default(fn () => auth()->check() ? auth()->id() : null)
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('lead_id')
                                    ->label('Consulta de Origen')
                                    ->relationship('lead', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "Lead #{$record->id} - {$record->customer?->name}")
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $lead = Lead::with('customer')->find($state);
                                        if (! $lead) {
                                            return;
                                        }

                                        // Customer linkage
                                        $set('holder_name', $lead->customer?->name);
                                        if ($lead->customer_id) {
                                            $set('customer_id', $lead->customer_id);
                                        }

                                        $aiData = $lead->ai_data ?? [];

                                        // Destination
                                        if (! empty($aiData['destino'])) {
                                            $set('destination', $aiData['destino']);
                                        } elseif (! empty($aiData['destination'])) {
                                            $set('destination', $aiData['destination']);
                                        }

                                        // Passengers
                                        $rawPass = $aiData['pasajeros'] ?? $aiData['passengers'] ?? null;
                                        if ($rawPass !== null) {
                                            session()->flash('lead_original_passengers', $rawPass);
                                            if (is_numeric(trim((string) $rawPass))) {
                                                $set('passengers', (int) $rawPass);
                                            } else {
                                                preg_match_all('/(\d+)\s*(adultos?|niñ[os|as]+|ninos?|menores?|bebes?|bebés?|pasajeros?|personas?|menor)/i', $rawPass, $matches);
                                                if (! empty($matches[1])) {
                                                    $set('passengers', array_sum($matches[1]));
                                                } else {
                                                    preg_match('/\d+/', $rawPass, $firstNum);
                                                    $set('passengers', ! empty($firstNum) ? (int) $firstNum[0] : 1);
                                                }
                                            }
                                        }

                                        // Nights
                                        if (! empty($aiData['noches']) && is_numeric($aiData['noches'])) {
                                            $set('nights', (int) $aiData['noches']);
                                        }

                                        // Travel date (ISO)
                                        if (! empty($aiData['travel_date'])) {
                                            $set('travel_date', $aiData['travel_date']);
                                        }

                                        // Internal notes — full lead context
                                        $parts = [];
                                        if (! empty($aiData['resumen'])) {
                                            $parts[] = "Resumen IA: {$aiData['resumen']}";
                                        }
                                        if (! empty($aiData['presupuesto'])) {
                                            $parts[] = "Presupuesto consultante: {$aiData['presupuesto']}";
                                        }
                                        if (! empty($aiData['ciudad_salida'])) {
                                            $parts[] = "Ciudad de salida: {$aiData['ciudad_salida']}";
                                        }
                                        if (! empty($aiData['fecha'])) {
                                            $parts[] = "Fechas solicitadas: {$aiData['fecha']}";
                                        }
                                        if ($lead->raw_message) {
                                            $parts[] = "Mensaje original: {$lead->raw_message}";
                                        }
                                        if (! empty($parts)) {
                                            $set('internal_notes', implode("\n", $parts));
                                        }

                                        // Notes visible al cliente
                                        if ($lead->ai_summary) {
                                            $set('notes', $lead->ai_summary);
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
                                    ->label(function (Get $get, ?Model $record) {
                                        $original = session('lead_original_passengers');

                                        if (! $original && $record && isset($record->lead_id)) {
                                            $lead = Lead::find($record->lead_id);
                                            if ($lead) {
                                                $original = $lead->ai_data['pasajeros'] ?? $lead->ai_data['passengers'] ?? null;
                                            }
                                        }

                                        if (! $original && $get('lead_id')) {
                                            $lead = Lead::find($get('lead_id'));
                                            if ($lead) {
                                                $original = $lead->ai_data['pasajeros'] ?? $lead->ai_data['passengers'] ?? null;
                                            }
                                        }

                                        return $original ? "Pasajeros ({$original})" : 'Pasajeros';
                                    })
                                    ->helperText(function (Get $get, ?Model $record) {
                                        $original = session('lead_original_passengers');

                                        if (! $original && $record && isset($record->lead_id)) {
                                            $lead = Lead::find($record->lead_id);
                                            if ($lead) {
                                                $original = $lead->ai_data['pasajeros'] ?? $lead->ai_data['passengers'] ?? null;
                                            }
                                        }

                                        if (! $original && $get('lead_id')) {
                                            $lead = Lead::find($get('lead_id'));
                                            if ($lead) {
                                                $original = $lead->ai_data['pasajeros'] ?? $lead->ai_data['passengers'] ?? null;
                                            }
                                        }

                                        return $original ? "Detalle de consulta: {$original}" : null;
                                    })
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

                Section::make('Pasajeros')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('bookingPassengers')
                            ->label('Listado de Pasajeros')
                            ->relationship('bookingPassengers')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('is_titular')
                                            ->label('Es Titular')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        TextInput::make('first_name')
                                            ->label('Nombres')
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('last_name')
                                            ->label('Apellidos')
                                            ->required()
                                            ->columnSpan(2),
                                    ]),
                                Grid::make(4)
                                    ->schema([
                                        Select::make('document_type')
                                            ->label('Tipo de Documento')
                                            ->options([
                                                'DNI' => 'DNI',
                                                'Pasaporte' => 'Pasaporte',
                                                'Otro' => 'Otro',
                                            ])
                                            ->default('DNI')
                                            ->required(),
                                        TextInput::make('document_number')
                                            ->label('Número de Documento')
                                            ->required(),
                                        FileUpload::make('passport_path')
                                            ->label('Foto del Pasaporte/DNI')
                                            ->image()
                                            ->directory('passports')
                                            ->maxSize(5120),
                                        DatePicker::make('document_expiration')
                                            ->label('Vencimiento Documento'),
                                        DatePicker::make('birth_date')
                                            ->label('Fecha de Nacimiento')
                                            ->required(),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('nationality')
                                            ->label('Nacionalidad'),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('phone')
                                            ->label('Teléfono'),
                                    ]),
                            ])
                            ->columns(1)
                            ->itemLabel(fn (array $state): ?string => ($state['first_name'] ?? '').' '.($state['last_name'] ?? '')),
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
                                            ->options(function () {
                                                $service = app(CurrencyService::class);
                                                $data = $service->getAllData();

                                                $options = ['ARS' => 'Pesos Argentinos (ARS)'];

                                                foreach ($data['currencies'] ?? [] as $key => $rate) {
                                                    $options[$key] = "{$rate['name']} ({$key})";
                                                }

                                                $options['OTHER'] = 'Otro';

                                                return $options;
                                            })
                                            ->default('USD')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $currency = $get('currency');
                                                if ($currency !== 'ARS' && $currency !== 'OTHER') {
                                                    $service = app(CurrencyService::class);
                                                    $rate = $service->getRate($currency, 'sell');
                                                    if ($rate > 1) {
                                                        $set('exchange_rate', $rate);
                                                    }
                                                } elseif ($currency === 'ARS') {
                                                    $set('exchange_rate', 1.00);
                                                }

                                                self::updateTotals($set, $get);
                                            }),

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
                                $serviceType = ServiceType::find($typeId);
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
                        FileUpload::make('vouchers')
                            ->label('Vouchers y Documentos de Viaje')
                            ->multiple()
                            ->directory('vouchers')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->columnSpanFull()
                            ->helperText('Sube los PDFs de vuelos, hoteles, etc. El cliente podrá descargarlos desde su portal.'),
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
