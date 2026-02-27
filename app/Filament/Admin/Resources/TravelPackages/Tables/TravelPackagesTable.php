<?php

namespace App\Filament\Admin\Resources\TravelPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TravelPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Portada')
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
                    ->money(fn($record) => $record->currency ?? 'USD')
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
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
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
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->required(),
                            ]),
                    ])
                    ->action(function (\App\Models\TravelPackage $record, array $data) {
                        $customer = \App\Models\Customer::find($data['customer_id']);
                        if (!$customer) {
                            $customer = \App\Models\Customer::create([
                                'name' => $data['name'],
                                'phone' => $data['phone'] ?? '',
                            ]);
                        }

                        $service = app(\App\Services\BudgetGenerationService::class);
                        $newBooking = $service->clonePackageToBudget($record, $customer);

                        \Filament\Notifications\Notification::make()
                            ->title('Presupuesto inicial creado')
                            ->body('Se cargaron los datos de la Idea de Viaje.')
                            ->success()
                            ->send();

                        return redirect(\App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $newBooking]));
                    }),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
