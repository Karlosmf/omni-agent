<?php

namespace App\Filament\Admin\Resources\FinancialAccounts;

use App\Filament\Admin\Resources\FinancialAccounts\Pages\CreateFinancialAccount;
use App\Filament\Admin\Resources\FinancialAccounts\Pages\EditFinancialAccount;
use App\Filament\Admin\Resources\FinancialAccounts\Pages\ListFinancialAccounts;
use App\Filament\Admin\Resources\FinancialAccounts\Schemas\FinancialAccountForm;
use App\Filament\Admin\Resources\FinancialAccounts\Tables\FinancialAccountsTable;
use App\Models\FinancialAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinancialAccountResource extends Resource
{
    protected static ?string $model = FinancialAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FinancialAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialAccountsTable::configure($table);
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
            'index' => ListFinancialAccounts::route('/'),
            'create' => CreateFinancialAccount::route('/create'),
            'edit' => EditFinancialAccount::route('/{record}/edit'),
        ];
    }
}
