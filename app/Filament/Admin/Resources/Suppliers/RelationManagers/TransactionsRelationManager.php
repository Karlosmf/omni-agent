<?php

namespace App\Filament\Admin\Resources\Suppliers\RelationManagers;

use App\Filament\Admin\Resources\Transactions\Schemas\TransactionForm; // Option to reuse if needed, but keeping simple table for now
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    // We can leave form empty to use defaults or configure it if needed
    // For now, let's allow creating transactions but we might need to handle supplier_id automatic assignment
    public function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('category.name')
                    ->label('Categoría'),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Método'),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                // AssociateAction::make(), // Usually not needed for HasMany unless polymorphic or BelongsToMany
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
}
