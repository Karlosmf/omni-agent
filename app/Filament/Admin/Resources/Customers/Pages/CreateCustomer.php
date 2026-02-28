<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public function getMaxWidth(): \Filament\Support\Enums\Width|string|null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    public ?string $lead_id = null;

    public function mount(): void
    {
        parent::mount();

        $data = [];
        if (request()->has('name')) {
            $data['name'] = request('name');
        }
        if (request()->has('email')) {
            $data['email'] = request('email');
        }
        if (request()->has('phone')) {
            $data['phone'] = request('phone');
        }
        if (request()->has('lead_id')) {
            $this->lead_id = request('lead_id');
        }

        if (! empty($data)) {
            $this->form->fill($data);
        }
    }

    protected function afterCreate(): void
    {
        if ($this->lead_id) {
            $lead = \App\Models\Lead::find($this->lead_id);
            if ($lead) {
                $lead->customer_id = $this->record->id;
                $lead->status = \App\Enums\LeadStatus::Closed;
                $lead->save();

                if ($lead->travel_package_id) {
                    $service = app(\App\Services\BudgetGenerationService::class);
                    $service->clonePackageToBudget($lead->travelPackage, $this->record, $lead->id);

                    \Filament\Notifications\Notification::make()
                        ->title('Presupuesto Creado')
                        ->body('Se generó el presupuesto en base a la Idea de Viaje consultada por el lead.')
                        ->success()
                        ->send();
                }
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Customer;
        if (empty($data['password'])) {
            $data['password'] = Hash::make(Str::random(12));
        }

        return $data;
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Crear registro')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
