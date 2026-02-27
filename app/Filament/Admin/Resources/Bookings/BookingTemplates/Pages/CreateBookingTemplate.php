<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages;

use App\Filament\Admin\Resources\Bookings\BookingTemplates\BookingTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingTemplate extends CreateRecord
{
    protected static bool $canCreateAnother = false;


    protected static string $resource = BookingTemplateResource::class;
}
