<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                ->action(function (Lead $record) {
                    if ($record->travelPackage && $record->customer) {
                        $service = app(\App\Services\BudgetGenerationService::class);
                        $booking = $service->clonePackageToBudget(
                            $record->travelPackage,
                            $record->customer,
                            $record->id,
                            null,
                            $record->ai_data['pasajeros'] ?? 1
                        );
                    } else {
                        $booking = Booking::create([
                            'lead_id' => $record->id,
                            'customer_id' => $record->customer_id,
                            'holder_name' => $record->customer?->name ?? 'A definir',
                            'destination' => $record->ai_data['destino'] ?? null,
                            'passengers' => $record->ai_data['pasajeros'] ?? 1,
                            'status' => BookingStatus::Borrador,
                            'travel_date' => now()->addMonths(1),
                            'valid_until' => now()->addDays(7),
                        ]);
                    }
                    $record->update(['status' => LeadStatus::Closed]);

                    return redirect()->to(BookingResource::getUrl('edit', ['record' => $booking->id]));
                }),
            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
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
