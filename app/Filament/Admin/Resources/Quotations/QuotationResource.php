<?php

namespace App\Filament\Admin\Resources\Quotations;

use App\Filament\Admin\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Admin\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Admin\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Admin\Resources\Quotations\Pages\ViewQuotation;
use App\Filament\Admin\Resources\Quotations\Schemas\QuotationForm;
use App\Filament\Admin\Resources\Quotations\Schemas\QuotationInfolist;
use App\Filament\Admin\Resources\Quotations\Tables\QuotationsTable;
use App\Models\Quotation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $modelLabel = 'Presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static ?string $navigationLabel = 'Presupuestos';

    protected static ?string $slug = 'presupuestos';

    protected static ?string $recordTitleAttribute = 'quotation_number';

    public static function form(Schema $schema): Schema
    {
        return QuotationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuotationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationsTable::configure($table);
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
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'view' => ViewQuotation::route('/{record}'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }
}
