<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(BookingStatus::class),
                Filter::make('travel_date')
                    ->label('Rango de Fecha Viaje')
                    ->form([
                        DatePicker::make('travel_date_from')
                            ->label('Desde'),
                        DatePicker::make('travel_date_to')
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
                            ->options(User::where('role', UserRole::Customer)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(fn (Booking $record) => $record->customer_id)
                            ->helperText('Selecciona el cliente para el duplicado.'),
                    ])
                    ->beforeReplicaSaved(function (Booking $replica, array $data) {
                        $replica->customer_id = $data['customer_id'];
                        $replica->lead_id = null;
                        $replica->file_number = null; // Generated on boot
                        $replica->status = BookingStatus::Borrador;
                        $replica->valid_until = now()->addDays(7);
                    })
                    ->afterReplicaSaved(function (Booking $replica, Booking $record) {
                        foreach ($record->items as $item) {
                            $newItem = $item->replicate();
                            $newItem->booking_id = $replica->id;
                            $newItem->save();
                        }
                    }),

                EditAction::make()
                    ->label('Editar'),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function ($record) {
                        $text = "Hola *{$record->holder_name}*! 👋\n";
                        if ($record->destination) {
                            $text .= "Aquí tienes tu cotización para *{$record->destination}* ✈️\n";
                        } else {
                            $text .= "Aquí tienes tu cotización ✈️\n";
                        }
                        $text .= "Total: *{$record->currency} ".number_format($record->total_sell, 2)."*\n";
                        $text .= '¡Avisanos si tenés alguna duda!';

                        return 'https://wa.me/?text='.urlencode($text);
                    })
                    ->openUrlInNewTab(),
                Action::make('pdf')
                    ->label('Presupuesto')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadView('pdf.booking', ['booking' => $record])->output();
                        }, 'presupuesto-'.$record->file_number.'.pdf');
                    }),
                Action::make('contract_pdf')
                    ->label('Acuerdo')
                    ->icon('heroicon-o-document-check')
                    ->color('warning')
                    ->visible(fn (Booking $record) => in_array($record->status, [BookingStatus::Senado, BookingStatus::Emitido]))
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            $settings = AgencySetting::first();
                            echo Pdf::loadView('pdf.contract', ['booking' => $record, 'settings' => $settings])->output();
                        }, 'acuerdo-'.$record->file_number.'.pdf');
                    }),
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
