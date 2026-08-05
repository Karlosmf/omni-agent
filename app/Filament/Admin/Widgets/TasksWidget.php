<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TasksWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Task::query()
                ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('agent_id', auth()->id()))
                ->where('is_completed', false)
            )
            ->heading('Mis Tareas Pendientes')
            ->defaultSort('due_date', 'asc')
            ->columns([
                ToggleColumn::make('is_completed')
                    ->label('Hecha'),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() ? 'danger' : 'gray'),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Task $record) => TaskResource::getUrl('index', ['tableSearch' => $record->title])),
            ]);
    }
}
