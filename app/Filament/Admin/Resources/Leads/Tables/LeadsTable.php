<?php

namespace App\Filament\Admin\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nombre')
                    ->searchable()
                    ->description(fn ($record) => collect([$record->customer_phone, $record->customer_email])->filter()->implode(' | ')),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                IconColumn::make('needs_human_attention')
                    ->label('Atención')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup('status')
            ->groups([
                \Filament\Tables\Grouping\Group::make('status')
                    ->label('Estado')
                    ->collapsible(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(LeadStatus::class),
                \Filament\Tables\Filters\TernaryFilter::make('needs_human_attention')
                    ->label('Atención Requerida'),
            ])
            ->recordActions([
                Action::make('convert_to_customer')
                    ->label('Convertir a Cliente')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->button()
                    ->url(fn (\App\Models\Lead $record) => \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('create', [
                        'name' => $record->customer_name,
                        'phone' => $record->customer_phone,
                        'email' => $record->customer_email,
                        'lead_id' => $record->id,
                    ]))
                    ->visible(fn (\App\Models\Lead $record) => is_null($record->customer_id)),
                \Filament\Actions\ActionGroup::make([
                    Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->url(function ($record) {
                            $settings = get_agency_settings();
                            $companyName = $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes');
                            $text = urlencode("Hola {$record->customer_name}, soy del equipo de {$companyName}. Te contacto por tu consulta sobre viajes. ¿En qué puedo ayudarte?");
                            return "https://wa.me/{$record->customer_phone}?text={$text}";
                        })
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => ! empty($record->customer_phone)),
                    EditAction::make()
                        ->label('Editar'),
                    Action::make('escalate')
                        ->label('Escalar a humano')
                        ->icon('heroicon-o-user-group')
                        ->color('warning')
                        ->action(fn ($record) => $record->update(['needs_human_attention' => true]))
                        ->visible(fn ($record) => ! $record->needs_human_attention),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exporters\LeadExporter::class)
                    ->label('Exportar')
                    ->icon('heroicon-o-arrow-down-tray'),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }
}
