<?php

namespace App\Filament\Admin\Resources\TransactionCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TransactionCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'ingreso' => 'Ingreso',
                        'egreso' => 'Egreso',
                    ])
                    ->required(),
                Toggle::make('is_system')
                    ->label('Es de Sistema')
                    ->default(false),
            ]);
    }
}
