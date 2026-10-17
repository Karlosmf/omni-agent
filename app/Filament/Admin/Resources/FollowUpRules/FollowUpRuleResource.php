<?php

namespace App\Filament\Admin\Resources\FollowUpRules;

use App\Enums\FollowUpAction;
use App\Enums\FollowUpTrigger;
use App\Filament\Admin\Resources\FollowUpRules\Pages\ManageFollowUpRules;
use App\Models\FollowUpRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class FollowUpRuleResource extends Resource
{
    protected static ?string $model = FollowUpRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'regla de seguimiento';

    protected static ?string $pluralModelLabel = 'reglas de seguimiento';

    protected static ?string $navigationLabel = 'Seguimiento automático';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(120),
                Select::make('trigger')->label('Cuándo')->options(FollowUpTrigger::class)->required(),
                TextInput::make('delay_hours')
                    ->label('Esperar (horas)')
                    ->helperText('Horas desde que se cumple la condición. 0 = en la próxima corrida.')
                    ->numeric()->integer()->minValue(0)->default(0)->required(),
                Select::make('action')->label('Qué hacer')->options(FollowUpAction::class)->required(),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Regla')->searchable(),
                TextColumn::make('trigger')->label('Cuándo')->badge(),
                TextColumn::make('delay_hours')->label('Espera')->suffix(' h'),
                TextColumn::make('action')->label('Acción'),
                ToggleColumn::make('is_active')->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFollowUpRules::route('/'),
        ];
    }
}
