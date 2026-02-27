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
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table, bool $onlyTemplates = false): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('is_template', $onlyTemplates))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('template_name')
                    ->label('Nombre Plantilla')
                    ->visible($onlyTemplates)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('file_number')
                    ->label('Nro')
                    ->hidden($onlyTemplates)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('holder_name')
                    ->label('Titular')
                    ->hidden($onlyTemplates)
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
                    ->money(fn($record) => $record->currency ?? 'USD')
                    ->sortable(),
                TextColumn::make('profit')
                    ->label('Ganancia')
                    ->money(fn($record) => $record->currency ?? 'USD')
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('travel_date')
                    ->label('Fecha Viaje')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Válido Hasta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->isExpired() ? 'danger' : 'gray')
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
                            ->when($data['travel_date_from'], fn($query, $date) => $query->whereDate('travel_date', '>=', $date))
                            ->when($data['travel_date_to'], fn($query, $date) => $query->whereDate('travel_date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->modalHeading('Duplicar Presupuesto / File')
                    ->hidden($onlyTemplates)
                    ->form([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(\App\Models\Customer::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(fn(\App\Models\Booking $record) => $record->customer_id)
                            ->helperText('Selecciona el cliente para el duplicado.'),
                    ])
                    ->beforeReplicaSaved(function (\App\Models\Booking $replica, array $data) {
                        $replica->customer_id = $data['customer_id'];
                        $replica->lead_id = null;
                        $replica->file_number = null; // Generated on boot
                        $replica->status = BookingStatus::Borrador;
                        $replica->valid_until = now()->addDays(7);
                        $replica->is_template = false;
                    })
                    ->afterReplicaSaved(function (\App\Models\Booking $replica, \App\Models\Booking $record) {
                        foreach ($record->items as $item) {
                            $newItem = $item->replicate();
                            $newItem->booking_id = $replica->id;
                            $newItem->save();
                        }
                    }),

                Action::make('useTemplate')
                    ->label('Usar Plantilla')
                    ->color('primary')
                    ->icon('heroicon-o-document-plus')
                    ->visible($onlyTemplates)
                    ->form([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (\App\Models\Booking $record, array $data) {
                        $newBooking = $record->replicate();
                        $newBooking->is_template = false;
                        $newBooking->template_name = null;
                        $newBooking->customer_id = $data['customer_id'];
                        $newBooking->status = BookingStatus::Borrador;
                        $newBooking->file_number = null;
                        $newBooking->save();

                        foreach ($record->items as $item) {
                            $newItem = $item->replicate();
                            $newItem->booking_id = $newBooking->id;
                            $newItem->save();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Presupuesto creado con éxito')
                            ->success()
                            ->send();

                        return redirect(\App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $newBooking]));
                    }),

                Action::make('saveAsTemplate')
                    ->label('Guardar como Plantilla')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->hidden($onlyTemplates)
                    ->form([
                        TextInput::make('template_name')
                            ->label('Nombre de la Plantilla')
                            ->required(),
                    ])
                    ->action(function (\App\Models\Booking $record, array $data) {
                        $template = $record->replicate();
                        $template->is_template = true;
                        $template->template_name = $data['template_name'];
                        $template->customer_id = null;
                        $template->lead_id = null;
                        $template->file_number = null;
                        $template->status = BookingStatus::Borrador;
                        $template->save();

                        foreach ($record->items as $item) {
                            $newItem = $item->replicate();
                            $newItem->booking_id = $template->id;
                            $newItem->save();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Plantilla guardada con éxito')
                            ->success()
                            ->send();
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
                        }, 'booking-' . $record->file_number . '.pdf');
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
