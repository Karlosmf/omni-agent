<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages;

use App\Filament\Admin\Resources\Bookings\BookingTemplates\BookingTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookingTemplate extends EditRecord
{
    protected static string $resource = BookingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
