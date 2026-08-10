<?php

namespace App\Filament\Admin\Resources\ServiceTypes;

use App\Filament\Admin\Resources\ServiceTypes\Pages\ManageServiceTypes;
use App\Models\ServiceType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'Tipo de Servicio';

    protected static ?string $pluralModelLabel = 'Tipos de Servicio';

    protected static ?string $navigationLabel = 'Tipos de Servicio';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('key')
                    ->label('Clave (slug)')
                    ->helperText('Dejar en blanco para generar automáticamente.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageServiceTypes::route('/'),
        ];
    }
}
