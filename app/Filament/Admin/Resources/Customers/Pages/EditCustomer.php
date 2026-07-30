<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Enums\PriceBasis;
use App\Filament\Admin\Concerns\HasBudgetGenerationModal;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\TravelPackage;
use App\Models\User;
use App\Services\BudgetGenerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_blank_budget')
                ->label('Crear Presupuesto en Blanco')
                ->icon('heroicon-o-document-plus')
                ->url(fn (User $record) => BookingResource::getUrl('create', ['customer_id' => $record->id])),

            Action::make('create_from_package')
                ->label('Crear desde Idea de Viaje')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->form(function (): array {
                    return [
                        Select::make('travel_package_id')
                            ->label('Seleccionar Idea de Viaje')
                            ->options(TravelPackage::pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        ...HasBudgetGenerationModal::schema(null),
                    ];
                })
                ->mountUsing(function ($form) {
                    // Re-fill price/basis when the package selection changes
                    $form->fill([]);
                })
                ->action(function (array $data, User $record) {
                    $package = TravelPackage::find($data['travel_package_id']);
                    $service = app(BudgetGenerationService::class);
                    $newBooking = $service->clonePackageToBudget(
                        $package,
                        $record,
                        travelDate: $data['travel_date'],
                        passengers: null,
                        priceOverride: isset($data['price_override']) ? (float) $data['price_override'] : null,
                        basisOverride: PriceBasis::tryFrom($data['basis_override'] ?? '') ?: null,
                        adults: (int) ($data['adults'] ?? 1),
                        children: (array) ($data['children'] ?? []),
                    );

                    Notification::make()
                        ->title('Presupuesto Creado')
                        ->body('Se importaron los datos de la Idea de Viaje.')
                        ->success()
                        ->send();

                    return redirect(BookingResource::getUrl('edit', ['record' => $newBooking]));
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
