<?php

namespace App\Filament\Admin\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Generales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre / Razón Social')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('category')
                                    ->label('Categoría')
                                    ->placeholder('Ej: Aerolínea, Hotel, Mayorista'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cuit')
                                    ->label('CUIT / ID Fiscal'),
                                TextInput::make('contact_email')
                                    ->label('Email de Contacto')
                                    ->email(),
                                TextInput::make('contact_phone')
                                    ->label('Teléfono'),
                            ]),
                    ]),

                Section::make('Cuentas Bancarias')
                    ->schema([
                        Repeater::make('accounts')
                            ->label('Cuentas')
                            ->relationship('accounts')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('bank_name')
                                            ->label('Banco')
                                            ->required(),
                                        Select::make('currency')
                                            ->label('Moneda')
                                            ->options(['ARS' => 'Pesos', 'USD' => 'Dólares'])
                                            ->default('ARS'),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('cbu')
                                            ->label('CBU / CVU'),
                                        TextInput::make('alias')
                                            ->label('Alias'),
                                        TextInput::make('account_number')
                                            ->label('Nro Cuenta'),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['bank_name'] ?? null)
                            ->addActionLabel('Agregar Cuenta (+)'),
                    ])
                    ->collapsible(),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
