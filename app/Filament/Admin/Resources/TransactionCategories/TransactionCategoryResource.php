<?php

namespace App\Filament\Admin\Resources\TransactionCategories;

use App\Filament\Admin\Resources\TransactionCategories\Pages\CreateTransactionCategory;
use App\Filament\Admin\Resources\TransactionCategories\Pages\EditTransactionCategory;
use App\Filament\Admin\Resources\TransactionCategories\Pages\ListTransactionCategories;
use App\Filament\Admin\Resources\TransactionCategories\Schemas\TransactionCategoryForm;
use App\Filament\Admin\Resources\TransactionCategories\Tables\TransactionCategoriesTable;
use App\Models\TransactionCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionCategoryResource extends Resource
{
    protected static ?string $model = TransactionCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TransactionCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionCategories::route('/'),
            'create' => CreateTransactionCategory::route('/create'),
            'edit' => EditTransactionCategory::route('/{record}/edit'),
        ];
    }
}
