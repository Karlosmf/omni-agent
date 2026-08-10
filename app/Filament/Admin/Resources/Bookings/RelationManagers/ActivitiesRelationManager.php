<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Historial de Actividad';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'status_changed' => 'warning',
                        'email_sent' => 'info',
                        'updated' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Creado',
                        'status_changed' => 'Cambio de Estado',
                        'email_sent' => 'Email Enviado',
                        'updated' => 'Actualizado',
                        default => $state,
                    }),
                TextColumn::make('description')
                    ->label('Detalle')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->default('Sistema'),
            ])
            ->paginated([10, 25, 50]);
    }
}
