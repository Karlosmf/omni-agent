<?php

namespace App\Filament\Admin\Resources\HeroSliders;

use App\Filament\Admin\Resources\HeroSliders\Pages\CreateHeroSlider;
use App\Filament\Admin\Resources\HeroSliders\Pages\EditHeroSlider;
use App\Filament\Admin\Resources\HeroSliders\Pages\ListHeroSliders;
use App\Filament\Admin\Resources\HeroSliders\Schemas\HeroSliderForm;
use App\Filament\Admin\Resources\HeroSliders\Tables\HeroSlidersTable;
use App\Models\HeroSlider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HeroSliderResource extends Resource
{
    protected static ?string $model = HeroSlider::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Sliders';

    protected static ?string $modelLabel = 'Slider';

    protected static ?string $pluralModelLabel = 'Sliders';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 110;

    public static function form(Schema $schema): Schema
    {
        return HeroSliderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HeroSlidersTable::configure($table);
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
            'index' => ListHeroSliders::route('/'),
            'create' => CreateHeroSlider::route('/create'),
            'edit' => EditHeroSlider::route('/{record}/edit'),
        ];
    }
}
