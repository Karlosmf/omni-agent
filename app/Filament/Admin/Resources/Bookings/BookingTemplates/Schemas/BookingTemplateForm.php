<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates\Schemas;

use Filament\Schemas\Schema;

class BookingTemplateForm
{
    public static function configure(Schema $schema, bool $isTemplate = false): Schema
    {
        return \App\Filament\Admin\Resources\Bookings\Schemas\BookingForm::configure($schema, $isTemplate);
    }
}
