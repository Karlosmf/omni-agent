<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Personales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255),
                                DatePicker::make('birth_date')
                                    ->label('Fecha de Nacimiento'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('doc_number')
                                    ->label('DNI / Documento'),
                                TextInput::make('passport_number')
                                    ->label('Pasaporte'),
                            ]),
                    ]),
                Section::make('Contacto')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Teléfono (WhatsApp)')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
                Section::make('Notas Internas')
                    ->columnSpanFull()
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
