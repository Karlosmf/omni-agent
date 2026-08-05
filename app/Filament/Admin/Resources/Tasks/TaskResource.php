<?php

namespace App\Filament\Admin\Resources\Tasks;

use App\Filament\Admin\Resources\Tasks\Pages\ManageTasks;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';

    protected static \UnitEnum|string|null $navigationGroup = 'Gestión';

    protected static ?string $modelLabel = 'Tarea';

    protected static ?string $pluralModelLabel = 'Tareas';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && ! $user->isAdmin()) {
            $query->where('agent_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3),
                Grid::make(2)
                    ->schema([
                        DatePicker::make('due_date')
                            ->label('Fecha Vencimiento'),
                        TimePicker::make('due_time')
                            ->label('Hora'),
                    ]),
                Select::make('agent_id')
                    ->label('Agente Asignado')
                    ->relationship('agent', 'name')
                    ->default(fn () => auth()->id())
                    ->required()
                    ->visible(fn () => auth()->user()?->isAdmin()),
                Toggle::make('is_completed')
                    ->label('Completada')
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                ToggleColumn::make('is_completed')
                    ->label('Hecha'),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && ! $record->is_completed ? 'danger' : 'gray'),
                TextColumn::make('agent.name')
                    ->label('Asignado a')
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->filters([
                TernaryFilter::make('is_completed')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Completadas')
                    ->falseLabel('Pendientes')
                    ->default(false),
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
            'index' => ManageTasks::route('/'),
        ];
    }
}
