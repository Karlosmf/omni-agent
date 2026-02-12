<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('file_number')
                    ->label('Nro')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('holder_name')
                    ->label('Titular')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->label('Destino')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_sell')
                    ->label('Venta')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable(),
                TextColumn::make('profit')
                    ->label('Ganancia')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('travel_date')
                    ->label('Fecha Viaje')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Válido Hasta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : 'gray')
                    ->visibleFrom('lg')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(BookingStatus::class),
                \Filament\Tables\Filters\Filter::make('travel_date')
                    ->label('Rango de Fecha Viaje')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('travel_date_from')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('travel_date_to')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['travel_date_from'], fn ($query, $date) => $query->whereDate('travel_date', '>=', $date))
                            ->when($data['travel_date_to'], fn ($query, $date) => $query->whereDate('travel_date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->modalHeading('Duplicar Presupuesto / File')
                    ->form([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(\App\Models\Customer::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(fn (\App\Models\Booking $record) => $record->customer_id)
                            ->helperText('Selecciona el cliente para el duplicado (puede ser el mismo u otro).'),
                    ])
                    ->beforeReplicaSaved(function (\App\Models\Booking $replica, array $data) {
                        $replica->customer_id = $data['customer_id'];
                        $replica->file_number = null;
                        $replica->status = BookingStatus::Borrador;
                        $replica->valid_until = now()->addDays(7);
                    }),
                EditAction::make()
                    ->label('Editar'),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadView('pdf.booking', ['booking' => $record])->output();
                        }, 'booking-'.$record->file_number.'.pdf');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
