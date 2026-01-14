<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.file_number')
                    ->label('Nro Expediente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Monto Orig.')
                    ->formatStateUsing(fn ($record) => $record->amount.' '.$record->currency->value)
                    ->sortable(),
                TextColumn::make('exchange_rate')
                    ->label('Tasa')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('amount_usd_fixed')
                    ->label('USD (Congelado)')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Método')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha Operación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Transacción')
                    ->options(TransactionType::class),
                \Filament\Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options(Currency::class),
                \Filament\Tables\Filters\SelectFilter::make('booking')
                    ->label('Expediente')
                    ->relationship('booking', 'file_number'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
