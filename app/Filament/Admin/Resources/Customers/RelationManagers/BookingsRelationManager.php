<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Expedientes';

    public function form(Schema $schema): Schema
    {
        return BookingResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_number')
            ->columns([
                Tables\Columns\TextColumn::make('file_number')
                    ->label('Nro Expediente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('holder_name')
                    ->label('Pasajero Principal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('travel_date')
                    ->label('Fecha Viaje')
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn () => BookingResource::getUrl('create')),
            ])
            ->actions([
                Action::make('pago')
                    ->label('Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->url(fn ($record) => \App\Filament\Admin\Resources\Transactions\TransactionResource::getUrl('create', ['booking_id' => $record->id])),
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => BookingResource::getUrl('edit', ['record' => $record])),
                EditAction::make()
                    ->url(fn ($record) => BookingResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
