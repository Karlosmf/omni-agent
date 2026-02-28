<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use App\Filament\Admin\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Transactions\Tables\TransactionsTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Movimientos de Caja';

    public function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Cargar Pago')
                    ->modalHeading('Nuevo Movimiento')
                    ->modalWidth('4xl')
                    ->createAnother(false),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash'),
            ]);
    }
}
