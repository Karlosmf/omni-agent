<?php

namespace App\Filament\Admin\Resources\TravelPackages;

use App\Filament\Admin\Resources\TravelPackages\Pages\CreateTravelPackage;
use App\Filament\Admin\Resources\TravelPackages\Pages\EditTravelPackage;
use App\Filament\Admin\Resources\TravelPackages\Pages\ListTravelPackages;
use App\Filament\Admin\Resources\TravelPackages\Schemas\TravelPackageForm;
use App\Filament\Admin\Resources\TravelPackages\Tables\TravelPackagesTable;
use App\Models\TravelPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class TravelPackageResource extends Resource
{
    protected static ?string $model = TravelPackage::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-americas';

    protected static UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Idea de Viaje';

    protected static ?string $pluralModelLabel = 'Ideas de Viaje';

    protected static ?string $navigationLabel = 'Ideas de Viaje';

    protected static ?string $slug = 'ideas-de-viaje';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TravelPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelPackagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelPackages::route('/'),
            'create' => CreateTravelPackage::route('/create'),
            'edit' => EditTravelPackage::route('/{record}/edit'),
        ];
    }
}
