<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Filament\Admin\Resources\Leads\LeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('convert_to_customer')
                ->label('Convertir a Cliente')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Convertir Lead a Cliente')
                ->modalDescription('¿Seguro que deseas crear un nuevo Cliente con los datos de este Lead?')
                ->action(function (\App\Models\Lead $record) {
                    $customer = \App\Models\Customer::create([
                        'name' => $record->customer_name,
                        'phone' => $record->customer_phone,
                        'email' => $record->customer_email,
                    ]);

                    $record->update([
                        'customer_id' => $customer->id,
                        'status' => \App\Enums\LeadStatus::Closed,
                    ]);

                    if ($record->travel_package_id) {
                        $service = app(\App\Services\BudgetGenerationService::class);
                        $service->clonePackageToBudget($record->travelPackage, $customer, $record->id);

                        \Filament\Notifications\Notification::make()
                            ->title('Cliente y Presupuesto Creados')
                            ->body('Se generó el presupuesto en base a la Idea de Viaje consultada.')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Cliente Creado')
                            ->success()
                            ->send();
                    }

                    return redirect()->to(\App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('edit', ['record' => $customer]));
                })
                ->visible(fn() => !$this->record->customer_id),
            DeleteAction::make(),
        ];
    }
}
