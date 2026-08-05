<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Bookings\Widgets\BookingFinancialSummary;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\TravelPackage;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Radio;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            BookingFinancialSummary::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('Cotización (WhatsApp)')
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
                ->label('Presupuesto (PDF)')
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
                ->label('Acuerdo (PDF)')
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
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['lead_id'])) {
            $lead = Lead::find($data['lead_id']);
            if ($lead) {
                $aiData = $lead->ai_data ?? [];
                $originalPassengersInfo = $aiData['pasajeros'] ?? $aiData['passengers'] ?? null;
                if ($originalPassengersInfo) {
                    session()->flash('lead_original_passengers', $originalPassengersInfo);
                }
            }
        }

        return $data;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
