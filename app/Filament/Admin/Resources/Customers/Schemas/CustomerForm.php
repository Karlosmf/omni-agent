<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos Personales')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Teléfono (WhatsApp)')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Group::make()
                            ->relationship('profile')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('doc_number')
                                            ->label('DNI / Documento'),
                                        TextInput::make('passport_number')
                                            ->label('Pasaporte'),
                                        DatePicker::make('birth_date')
                                            ->label('Fecha de Nacimiento'),
                                    ]),
                                Textarea::make('address')
                                    ->label('Domicilio Completo')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                    ]),
            ]);
    }
}
