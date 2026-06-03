<?php

namespace App\Filament\Admin\Resources\Suppliers\RelationManagers;

use App\Enums\TransactionType;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title=created_at')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('booking.file_number')
                    ->label('File')
                    ->searchable()
                    ->url(fn ($record) => $record->booking ? BookingResource::getUrl('edit', ['record' => $record->booking]) : null),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->colors([
                        'success' => TransactionType::Cobro,
                        'danger' => TransactionType::Pago,
                    ]),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money(fn ($record) => $record->currency?->value ?? 'USD')
                    ->sortable(),
                TextColumn::make('amount_usd_fixed')
                    ->label('Monto (USD)')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Método')
                    ->searchable(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
