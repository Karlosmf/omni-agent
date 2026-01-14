<?php

namespace App\Filament\Admin\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nombre')
                    ->searchable()
                    ->description(fn ($record) => $record->customer_phone),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge(),
                TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                IconColumn::make('needs_human_attention')
                    ->label('Atención')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultGroup('temperature')
            ->groups([
                \Filament\Tables\Grouping\Group::make('temperature')
                    ->label('Temperatura')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('status')
                    ->label('Estado')
                    ->collapsible(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(LeadStatus::class),
                \Filament\Tables\Filters\SelectFilter::make('temperature')
                    ->label('Temperatura')
                    ->options(LeadTemperature::class),
                \Filament\Tables\Filters\TernaryFilter::make('needs_human_attention')
                    ->label('Atención Requerida'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),
                Action::make('escalate')
                    ->label('Escalar a humano')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->action(fn ($record) => $record->update(['needs_human_attention' => true]))
                    ->visible(fn ($record) => ! $record->needs_human_attention),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
