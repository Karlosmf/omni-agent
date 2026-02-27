<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class BookingTemplatesTable
{
    public static function configure(Table $table, bool $onlyTemplates = false): Table
    {
        return \App\Filament\Admin\Resources\Bookings\Tables\BookingsTable::configure($table, $onlyTemplates);
    }
}
