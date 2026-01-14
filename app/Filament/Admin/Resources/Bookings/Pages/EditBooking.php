<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
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
            Action::make('pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdf.booking', ['booking' => $record])->output();
                    }, 'booking-'.$record->file_number.'.pdf');
                }),
            DeleteAction::make(),
        ];
    }
}
