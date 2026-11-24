<?php

namespace App\Filament\Admin\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Services\Bot\TripData;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->label('Cliente Asociado')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
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
                                    ->required()
                                    ->live(),
                                Select::make('agent_id')
                                    ->label('Agente Asignado')
                                    ->relationship('agent', 'name', fn ($query) => $query->where('role', UserRole::Sales)->orWhere('role', UserRole::Admin))
                                    ->default(fn () => auth()->check() ? auth()->id() : null)
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Grid::make(3)
                            ->visible(fn (Get $get): bool => self::isClosed($get('status')))
                            ->schema([
                                Select::make('lost_reason')
                                    ->label('Motivo de pérdida')
                                    ->helperText('Dejalo vacío si se cerró por otra razón (por ejemplo, el viaje ya se hizo).')
                                    ->options(LostReason::class),
                                Textarea::make('lost_note')
                                    ->label('Detalle')
                                    ->maxLength(500)
                                    ->rows(2)
                                    ->columnSpan(2),
                            ]),
                    ]),
                Section::make('Datos del viaje')
                    ->description('Lo que juntó el bot recepcionista.')
                    ->visible(fn (?Lead $record): bool => filled($record?->trip_data))
                    ->schema([
                        TextEntry::make('trip_summary')
                            ->hiddenLabel()
                            ->state(fn (Lead $record): array => collect(TripData::summary($record->trip_data))
                                ->map(fn (string $value, string $label) => "{$label}: {$value}")
                                ->values()
                                ->all())
                            ->listWithLineBreaks(),
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
                Section::make('Primer mensaje')
                    ->description('Lo que escribió el cliente al abrir la consulta. La conversación completa está en Chat → Bandeja de mensajes.')
                    ->collapsed()
                    ->schema([
                        Textarea::make('raw_message')
                            ->hiddenLabel()
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function isClosed(mixed $status): bool
    {
        return $status === LeadStatus::Closed || $status === LeadStatus::Closed->value;
    }
}
