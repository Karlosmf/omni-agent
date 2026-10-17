<?php

namespace App\Filament\Admin\Resources\PackageImports;

use App\Filament\Admin\Resources\PackageImports\Pages\CreatePackageImport;
use App\Filament\Admin\Resources\PackageImports\Pages\ListPackageImports;
use App\Filament\Admin\Resources\PackageImports\Pages\ReviewPackageImport;
use App\Filament\Admin\Resources\PackageImports\Schemas\PackageImportForm;
use App\Filament\Admin\Resources\PackageImports\Tables\PackageImportsTable;
use App\Models\PackageImport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PackageImportResource extends Resource
{
    protected static ?string $model = PackageImport::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'importación';

    protected static ?string $pluralModelLabel = 'importaciones';

    protected static ?string $navigationLabel = 'Importar Ideas de Viaje';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_packages', 'manage_leads', 'manage_bookings']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return PackageImportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackageImportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackageImports::route('/'),
            'create' => CreatePackageImport::route('/create'),
            'review' => ReviewPackageImport::route('/{record}/review'),
        ];
    }
}
