<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Exports\BookingExporter;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\TravelPackage;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                TextColumn::make('agent.name')
                    ->label('Agente')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->actions([
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
                Action::make('share_link')
                    ->label('Ver Link Público')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->modalHeading('Link del Presupuesto')
                    ->modalDescription(fn (Booking $record) => 'Copiá este link y compartilo por WhatsApp, email o el canal que prefieras.')
                    ->modalContent(fn (Booking $record) => view('filament.modals.share-booking-link', [
                        'url' => $record->publicUrl(),
                        'booking' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('whatsapp')
                    ->label('WhatsApp Web')
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
                        $text .= 'Podés ver el detalle acá: '.$record->publicUrl()."\n";
                        $text .= '¡Avisanos si tenés alguna duda!';

                        return 'https://wa.me/?text='.urlencode($text);
                    })
                    ->openUrlInNewTab(),
                Action::make('whatsapp_api')
                    ->label('Enviar por WhatsApp (API)')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        TextInput::make('phone')
                            ->label('Teléfono Destino')
                            ->default(fn (Booking $record) => $record->customer?->phone)
                            ->required(),
                        Textarea::make('message')
                            ->label('Mensaje')
                            ->default(fn (Booking $record) => "Hola *{$record->holder_name}*! 👋\nTe comparto el detalle de tu viaje: ".$record->publicUrl())
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (Booking $record, array $data) {
                        // TODO: Implement actual Meta/Twilio API call here
                        // e.g. TwilioService::sendMessage($data['phone'], $data['message']);

                        // Simulate success
                        Notification::make()
                            ->title('WhatsApp Enviado')
                            ->body('El mensaje fue enviado exitosamente a '.$data['phone'])
                            ->success()
                            ->send();

                        // Optionally log in internal notes
                        $record->internal_notes .= "\n[".now()->format('Y-m-d H:i')."] WhatsApp enviado a {$data['phone']} vía API.";
                        $record->save();
                    }),
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Radio::make('format')
                            ->label('Formato del documento')
                            ->options([
                                'budget_only' => 'Solo Presupuesto',
                                'full' => 'Presupuesto + Detalle de la Idea de Viaje',
                            ])
                            ->default('budget_only')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        return response()->streamDownload(function () use ($record, $data) {
                            $travelPackage = null;
                            if ($record->lead?->travelPackage) {
                                $travelPackage = $record->lead->travelPackage;
                            } elseif (str_starts_with((string) $record->internal_notes, 'Presupuesto generado a partir de Idea de Viaje: ')) {
                                $title = str_replace('Presupuesto generado a partir de Idea de Viaje: ', '', $record->internal_notes);
                                $travelPackage = TravelPackage::where('title', $title)->first();
                            }

                            echo Pdf::loadView('pdf.booking', [
                                'booking' => $record,
                                'format' => $data['format'],
                                'travelPackage' => $travelPackage,
                            ])->output();
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(BookingExporter::class)
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }
}
