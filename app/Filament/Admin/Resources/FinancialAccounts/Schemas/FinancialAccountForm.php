<?php

namespace App\Filament\Admin\Resources\FinancialAccounts\Schemas;

use App\Enums\Currency;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FinancialAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Select::make('currency')
                    ->label('Moneda')
                    ->options(Currency::class)
                    ->required(),
                TextInput::make('balance')
                    ->label('Saldo Inicial')
                    ->numeric()
                    ->default(0),
                TextInput::make('cbu')
                    ->label('CBU / Alias'),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }
}
