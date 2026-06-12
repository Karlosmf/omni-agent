<?php

namespace App\Filament\Admin\Resources\TravelPackages\Tables;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\TravelPackage;
use App\Models\User;
use App\Services\BudgetGenerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TravelPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Portada')
                    ->checkFileExistence(false)
                    ->getStateUsing(fn ($record): ?string => $record->cover_image
                            ? (str_starts_with($record->cover_image, 'http')
                                ? $record->cover_image
                                : asset('storage/'.$record->cover_image))
                            : null
                    )
                    ->circular(false)
                    ->height(60)
                    ->width(80),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('destination')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nights')
                    ->label('Noches')
                    ->sortable()
                    ->suffix(' noches'),
                TextColumn::make('price_from')
                    ->label('Precio Desde')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->recordActions([
                Action::make('createBudget')
                    ->label('Presupuestar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(User::where('role', UserRole::Customer)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $data['role'] = UserRole::Customer;
                                $data['password'] = Hash::make(Str::random(12));

                                return User::create($data)->id;
                            }),
                        DatePicker::make('travel_date')
                            ->label('Fecha Estimada de Viaje')
                            ->default(now()->addMonths(3))
                            ->required(),
                        TextInput::make('passengers')
                            ->label('Cantidad de Pasajeros')
                            ->numeric()
                            ->default(2)
                            ->required(),
                    ])
                    ->action(function (TravelPackage $record, array $data) {
                        $customer = User::find($data['customer_id']);
                        if (! $customer) {
                            $customer = User::create([
                                'name' => $data['name'],
                                'phone' => $data['phone'] ?? '',
                                'role' => UserRole::Customer,
                                'password' => Hash::make(Str::random(12)),
                            ]);
                        }

                        $service = app(BudgetGenerationService::class);
                        $newBooking = $service->clonePackageToBudget(
                            $record,
                            $customer,
                            travelDate: $data['travel_date'],
                            passengers: $data['passengers']
                        );

                        Notification::make()
                            ->title('Presupuesto inicial creado')
                            ->body('Se cargaron los datos de la Idea de Viaje.')
                            ->success()
                            ->send();

                        return redirect(BookingResource::getUrl('edit', ['record' => $newBooking]));
                    }),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }
}
