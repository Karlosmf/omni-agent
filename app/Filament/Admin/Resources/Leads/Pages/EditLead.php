<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\PriceBasis;
use App\Filament\Admin\Concerns\HasBudgetGenerationModal;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use App\Services\BudgetGenerationService;
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
                ->form(function (): array {
                    $package = $this->record->travelPackage ?? null;

                    return HasBudgetGenerationModal::schema($package);
                })
                ->fillForm(function (Lead $record) {
                    return [
                        'adults' => $record->ai_data['pasajeros'] ?? 2,
                        'children' => [],
                    ];
                })
                ->action(function (Lead $record, array $data) {
                    if ($record->travelPackage && $record->customer) {
                        $service = app(BudgetGenerationService::class);
                        $booking = $service->clonePackageToBudget(
                            $record->travelPackage,
                            $record->customer,
                            $record->id,
                            $data['travel_date'] ?? null,
                            null,
                            isset($data['price_override']) ? (float) $data['price_override'] : null,
                            PriceBasis::tryFrom($data['basis_override'] ?? '') ?: null,
                            (int) ($data['adults'] ?? 1),
                            (array) ($data['children'] ?? []),
                        );
                    } else {
                        $booking = Booking::create([
                            'lead_id' => $record->id,
                            'customer_id' => $record->customer_id,
                            'holder_name' => $record->customer?->name ?? 'A definir',
                            'destination' => $record->ai_data['destino'] ?? null,
                            'passengers' => (int) (($data['adults'] ?? 1) + count($data['children'] ?? [])),
                            'status' => BookingStatus::Borrador,
                            'travel_date' => $data['travel_date'] ?? now()->addMonths(1),
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
