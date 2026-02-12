<?php

namespace App\Filament\Admin\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
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
                TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->badge()
                    ->sortable()
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
            ->defaultGroup('temperature')
            ->groups([
                \Filament\Tables\Grouping\Group::make('temperature')
                    ->label('Temperatura')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('status')
                    ->label('Estado')
                    ->collapsible(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(LeadStatus::class),
                \Filament\Tables\Filters\SelectFilter::make('temperature')
                    ->label('Temperatura')
                    ->options(LeadTemperature::class),
                \Filament\Tables\Filters\TernaryFilter::make('needs_human_attention')
                    ->label('Atención Requerida'),
            ])
            ->recordActions([
                Action::make('convert_to_customer')
                    ->label('Convertir a Cliente')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Convertir Lead a Cliente')
                    ->modalDescription('¿Seguro que deseas crear un nuevo Cliente con los datos de este Lead? Se redirigirá a la edición del cliente.')
                    ->action(function (\App\Models\Lead $record) {
                        $customer = \App\Models\Customer::create([
                            'name' => $record->customer_name,
                            'phone' => $record->customer_phone,
                            'email' => $record->customer_email,
                        ]);

                        $record->update([
                            'customer_id' => $customer->id,
                            'status' => \App\Enums\LeadStatus::Closed, // Marking as closed/converted
                        ]);

                        return redirect()->to(\App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('edit', ['record' => $customer]));
                    })
                    ->visible(fn (\App\Models\Lead $record) => is_null($record->customer_id)),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(fn ($record) => "https://wa.me/{$record->customer_phone}?text=".urlencode("Hola {$record->customer_name}, soy del equipo de Luopan Viajes. Te contacto por tu consulta sobre viajes. ¿En qué puedo ayudarte?"))
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
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exporters\LeadExporter::class)
                    ->label('Exportar')
                    ->icon('heroicon-o-arrow-down-tray'),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
