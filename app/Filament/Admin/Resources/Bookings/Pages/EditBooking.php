<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\AgencySetting;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

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
                ->action(function ($record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdf.booking', ['booking' => $record])->output();
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
