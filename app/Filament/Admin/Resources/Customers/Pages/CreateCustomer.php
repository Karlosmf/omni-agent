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

        if (!empty($data)) {
            $this->form->fill($data);
        }
    }

    protected function afterCreate(): void
    {
        if ($this->lead_id) {
            $lead = \App\Models\Lead::find($this->lead_id);
            if ($lead) {
                $lead->customer_id = $this->record->id;
                $lead->save();
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
}
