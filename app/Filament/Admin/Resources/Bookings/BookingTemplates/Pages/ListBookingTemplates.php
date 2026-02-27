<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages;

use App\Filament\Admin\Resources\Bookings\BookingTemplates\BookingTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookingTemplates extends ListRecords
{
    protected static string $resource = BookingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
