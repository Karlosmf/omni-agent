<?php

namespace App\Filament\Admin\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
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
                Section::make('Información del Interesado')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Nombre Completo')
                                    ->required(),
                                TextInput::make('customer_phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->required(),
                                TextInput::make('customer_email')
                                    ->label('Correo Electrónico')
                                    ->email(),
                                TextInput::make('customer_budget')
                                    ->label('Presupuesto Estimado'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('source')
                                    ->label('Canal / Origen')
                                    ->placeholder('Ej: WhatsApp, Instagram, Web')
                                    ->required(),
                                Select::make('status')
                                    ->label('Estado de la Consulta')
                                    ->options(LeadStatus::class)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Análisis de IA')
                    ->schema([
                        KeyValue::make('ai_data')
                            ->label('Datos Extraídos por IA'),
                        Textarea::make('ai_summary')
                            ->label('Resumen de la Conversación')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('needs_human_attention')
                            ->label('Requiere intervención manual')
                            ->required(),
                    ]),
                Section::make('Interacción Original')
                    ->label('Historial de Chat')
                    ->collapsed()
                    ->schema([
                        Textarea::make('raw_message')
                            ->label('Conversación Completa')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
