<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
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
                Section::make('Datos del pasajero')
                    ->description('Los recuerda el bot entre viajes: en una consulta nueva solo los confirma.')
                    ->visible(fn (?User $record): bool => filled($record?->traveler_profile))
                    ->schema([
                        TextEntry::make('traveler_summary')
                            ->hiddenLabel()
                            ->state(fn (User $record): array => collect($record->traveler_profile)
                                ->map(fn (mixed $value, string $key) => str_replace('_', ' ', ucfirst($key)).': '.match (true) {
                                    is_bool($value) => $value ? 'Sí' : 'No',
                                    is_array($value) => implode(', ', $value),
                                    default => (string) $value,
                                })
                                ->values()
                                ->all())
                            ->listWithLineBreaks(),
                    ]),
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
