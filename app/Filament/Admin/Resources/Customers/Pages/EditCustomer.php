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

    public function getMaxWidth(): \Filament\Support\Enums\Width|string|null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_blank_budget')
                ->label('Crear Presupuesto en Blanco')
                ->icon('heroicon-o-document-plus')
                ->url(fn (\App\Models\User $record) => \App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('create', ['customer_id' => $record->id])),

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
                    \Filament\Forms\Components\DatePicker::make('travel_date')
                        ->label('Fecha Estimada de Viaje')
                        ->default(now()->addMonths(3))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('passengers')
                        ->label('Cantidad de Pasajeros')
                        ->numeric()
                        ->default(2)
                        ->required(),
                ])
                ->action(function (array $data, \App\Models\User $record) {
                    $package = \App\Models\TravelPackage::find($data['travel_package_id']);
                    $service = app(\App\Services\BudgetGenerationService::class);
                    $newBooking = $service->clonePackageToBudget(
                        $package,
                        $record,
                        travelDate: $data['travel_date'],
                        passengers: $data['passengers']
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Presupuesto Creado')
                        ->body('Se importaron los datos de la Idea de Viaje.')
                        ->success()
                        ->send();

                    return redirect(\App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $newBooking]));
                }),

            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
