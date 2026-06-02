<?php

namespace App\Filament\Customer\Resources\Bookings\Pages;

use App\Filament\Customer\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBookings extends ManageRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
