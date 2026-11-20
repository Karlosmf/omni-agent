<?php

namespace App\Filament\Admin\Resources\Leads\Tables;

use App\Actions\Leads\CreateBookingFromLeadAction;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Filament\Admin\Concerns\HasBudgetGenerationModal;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Leads\Pages\EditLead;
use App\Filament\Exporters\LeadExporter;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Nombre')
                    ->searchable()
                    ->description(fn (Lead $record) => collect([$record->destination(), $record->customer?->phone])->filter()->implode(' · ')),
                TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->visibleFrom('md'),
                TextColumn::make('agent.name')
                    ->label('Agente')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                IconColumn::make('needs_human_attention')
                    ->label('Atención')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('next_follow_up_at')
                    ->label('Próximo seguimiento')
                    ->dateTime('d/m H:i')
                    ->placeholder('—')
                    ->color(fn (Lead $record) => $record->isFollowUpOverdue() ? 'danger' : 'gray')
                    ->weight(fn (Lead $record) => $record->isFollowUpOverdue() ? 'bold' : null)
                    ->sortable(),
                TextColumn::make('last_contacted_at')
                    ->label('Último contacto')
                    ->since()
                    ->placeholder('Sin contactar')
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Most urgent first. Grouping by status stays available, but off by default so it does not break this order.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByPriority())
            ->defaultSortOptionLabel('Prioridad')
            ->groups([
                Group::make('status')
                    ->label('Estado')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(LeadStatus::class),
                SelectFilter::make('temperature')
                    ->label('Temperatura')
                    ->options(LeadTemperature::class),
                Filter::make('overdue_follow_up')
                    ->label('Seguimiento atrasado')
                    ->query(fn (Builder $query) => $query->followUpOverdue()),
                TernaryFilter::make('needs_human_attention')
                    ->label('Atención Requerida'),
            ])
            ->recordActions([
                Action::make('view_customer')
                    ->label('Ver Cliente')
                    ->icon('heroicon-o-user')
                    ->color('primary')
                    ->button()
                    ->url(fn (Lead $record) => CustomerResource::getUrl('edit', ['record' => $record->customer_id]))
                    ->visible(fn (Lead $record) => ! is_null($record->customer_id)),
                ActionGroup::make([
                    Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->url(function ($record) {
                            $settings = get_agency_settings();
                            $companyName = $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes');
                            $text = urlencode("Hola {$record->customer?->name}, soy del equipo de {$companyName}. Te contacto por tu consulta sobre viajes. ¿En qué puedo ayudarte?");

                            return "https://wa.me/{$record->customer?->phone}?text={$text}";
                        })
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => ! empty($record->customer?->phone)),
                    EditAction::make()
                        ->label('Editar'),
                    Action::make('escalate')
                        ->label('Escalar a humano')
                        ->icon('heroicon-o-user-group')
                        ->color('warning')
                        ->action(fn ($record) => $record->update(['needs_human_attention' => true]))
                        ->visible(fn ($record) => ! $record->needs_human_attention),
                    Action::make('create_booking')
                        ->label('Crear Expediente')
                        ->icon('heroicon-o-folder-plus')
                        ->color('primary')
                        ->form(fn (Lead $record): array => HasBudgetGenerationModal::schema($record->travelPackage, withLeadDetails: true))
                        ->fillForm(fn (Lead $record): array => EditLead::bookingFormDefaults($record))
                        ->action(function (Lead $record, array $data) {
                            $booking = app(CreateBookingFromLeadAction::class)->execute($record, $data);

                            return redirect()->to(BookingResource::getUrl('edit', ['record' => $booking->id]));
                        }),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->toolbarActions([
                ExportAction::make()
                    ->exporter(LeadExporter::class)
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
