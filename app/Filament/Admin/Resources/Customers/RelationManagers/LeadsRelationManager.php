<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Services\Bot\TripData;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The contact's inquiries, one per trip.
 */
class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    protected static ?string $title = 'Consultas';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('destination')
                    ->label('Destino')
                    ->state(fn (Lead $record): string => TripData::of($record)['destino'] ?? 'Sin destino todavía'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('agent.name')->label('Asignada a')->placeholder('Sin asignar'),
                TextColumn::make('channel')->label('Canal')->placeholder('—'),
                TextColumn::make('last_inbound_at')->label('Último mensaje')->since()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('conversation')
                    ->label('Conversación')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (Lead $record): string => WhatsAppInbox::getUrl(['lead' => $record->id])),
                Action::make('open')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Lead $record): string => LeadResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
