<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Lead;
use App\Services\BudgetGenerationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
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
            $lead = Lead::find($this->lead_id);
            if ($lead) {
                $lead->customer_id = $this->record->id;
                $lead->status = LeadStatus::Closed;
                $lead->save();

                if ($lead->travel_package_id) {
                    $service = app(BudgetGenerationService::class);
                    $service->clonePackageToBudget($lead->travelPackage, $this->record, $lead->id);

                    Notification::make()
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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear registro')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
