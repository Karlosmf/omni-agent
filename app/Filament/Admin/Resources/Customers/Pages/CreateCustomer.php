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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Customer;
        if (empty($data['password'])) {
            $data['password'] = Hash::make(Str::random(12));
        }

        return $data;
    }
}
