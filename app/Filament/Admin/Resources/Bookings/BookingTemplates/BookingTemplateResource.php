<?php

namespace App\Filament\Admin\Resources\Bookings\BookingTemplates;

use App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages\CreateBookingTemplate;
use App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages\EditBookingTemplate;
use App\Filament\Admin\Resources\Bookings\BookingTemplates\Pages\ListBookingTemplates;
use App\Filament\Admin\Resources\Bookings\BookingTemplates\Schemas\BookingTemplateForm;
use App\Filament\Admin\Resources\Bookings\BookingTemplates\Tables\BookingTemplatesTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingTemplateResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static \UnitEnum|string|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Plantilla de Viaje';

    protected static ?string $pluralModelLabel = 'Plantillas de Viaje';

    protected static ?string $navigationLabel = 'Plantillas de Viaje';

    protected static ?string $slug = 'plantillas-viaje';

    public static function form(Schema $schema): Schema
    {
        return BookingTemplateForm::configure($schema, true);
    }

    public static function table(Table $table): Table
    {
        return BookingTemplatesTable::configure($table, true);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingTemplates::route('/'),
            'create' => CreateBookingTemplate::route('/create'),
            'edit' => EditBookingTemplate::route('/{record}/edit'),
        ];
    }
}
