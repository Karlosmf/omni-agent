<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_blank_budget')
                ->label('Crear Presupuesto en Blanco')
                ->icon('heroicon-o-document-plus')
                ->url(fn(\App\Models\Customer $record) => \App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('create', ['customer_id' => $record->id])),

            Action::make('create_from_package')
                ->label('Crear desde Idea de Viaje')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->form([
                    Select::make('travel_package_id')
                        ->label('Seleccionar Idea de Viaje')
                        ->options(\App\Models\TravelPackage::pluck('title', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data, \App\Models\Customer $record) {
                    $package = \App\Models\TravelPackage::find($data['travel_package_id']);
                    $service = app(\App\Services\BudgetGenerationService::class);
                    $newBooking = $service->clonePackageToBudget($package, $record);

                    \Filament\Notifications\Notification::make()
                        ->title('Presupuesto Creado')
                        ->body('Se importaron los datos de la Idea de Viaje.')
                        ->success()
                        ->send();

                    return redirect(\App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $newBooking]));
                }),

            DeleteAction::make(),
        ];
    }
}
