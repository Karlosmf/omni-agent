<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_number')
                    ->label('Nro Expediente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('holder_name')
                    ->label('Titular')
                    ->searchable()
                    ->sortable(),
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
                    ->date()
                    ->sortable(),
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
