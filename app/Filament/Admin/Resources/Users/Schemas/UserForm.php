<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
// Added
use Filament\Forms\Components\TextInput; // Updated
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Email verificado el'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),

                Section::make('Permisos')
                    ->description('Selecciona los permisos para este usuario')
                    ->schema([
                        \Filament\Forms\Components\CheckboxList::make('permissions')
                            ->label('Accesos Permitidos')
                            ->options([
                                'manage_leads' => 'Gestionar Consultas',
                                'manage_bookings' => 'Gestionar Presupuestos / Files',
                                'manage_customers' => 'Gestionar Clientes',
                                'manage_transactions' => 'Gestionar Caja',
                                'manage_users' => 'Gestionar Usuarios',
                                'view_financial_reports' => 'Ver Reportes Financieros',
                            ])
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ])
                    ->collapsed(false),
            ]);
    }
}
