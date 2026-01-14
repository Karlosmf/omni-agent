<?php

namespace App\Filament\Admin\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Cliente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Nombre del Cliente')
                                    ->required(),
                                TextInput::make('customer_phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('source')
                                    ->label('Origen')
                                    ->required(),
                                Select::make('temperature')
                                    ->label('Temperatura')
                                    ->options(LeadTemperature::class)
                                    ->required(),
                                Select::make('status')
                                    ->label('Estado')
                                    ->options(LeadStatus::class)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Análisis de IA')
                    ->schema([
                        KeyValue::make('ai_data')
                            ->label('Datos Extraídos'),
                        Textarea::make('ai_summary')
                            ->label('Resumen de IA')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('needs_human_attention')
                            ->label('Requiere atención humana')
                            ->required(),
                    ]),
                Section::make('Interacción Original')
                    ->label('Mensaje Original')
                    ->collapsed()
                    ->schema([
                        Textarea::make('raw_message')
                            ->label('Chat Completo')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
