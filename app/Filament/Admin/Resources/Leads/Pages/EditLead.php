<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Actions\Leads\CreateBookingFromLeadAction;
use App\Filament\Admin\Concerns\HasBudgetGenerationModal;
use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Services\LeadBookingDraftService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_chat')
                ->label('Abrir chat')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->url(fn (Lead $record): string => WhatsAppInbox::getUrl(['lead' => $record->id]))
                ->visible(fn (): bool => $this->record->messages()->exists()),

            Action::make('view_customer')
                ->label('Ver Cliente')
                ->icon('heroicon-o-user')
                ->color('primary')
                ->url(fn (Lead $record) => CustomerResource::getUrl('edit', ['record' => $record->customer_id]))
                ->visible(fn () => ! is_null($this->record->customer_id)),

            Action::make('create_booking')
                ->label('Crear Expediente')
                ->icon('heroicon-o-folder-plus')
                ->color('success')
                ->form(fn (): array => HasBudgetGenerationModal::schema($this->record->travelPackage, withLeadDetails: true))
                ->fillForm(fn (Lead $record): array => self::bookingFormDefaults($record))
                ->action(function (Lead $record, array $data) {
                    $booking = app(CreateBookingFromLeadAction::class)->execute($record, $data);

                    return redirect()->to(BookingResource::getUrl('edit', ['record' => $booking->id]));
                }),

            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * Values for the "Crear Expediente" form: what the AI could read from the whole conversation.
     *
     * @return array<string, mixed>
     */
    public static function bookingFormDefaults(Lead $lead): array
    {
        $draft = app(LeadBookingDraftService::class)->draftFor($lead);

        return array_filter([
            'travel_date' => $draft['travel_date'],
            'adults' => $draft['adults'],
            'children' => $draft['children'],
            'holder_name' => $draft['holder_name'],
            'destination' => $draft['destination'],
            'nights' => $draft['nights'],
            'notes' => $draft['notes'],
            'price_override' => $lead->travelPackage ? null : $draft['budget_usd'],
        ], fn ($value) => $value !== null);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
