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
            \Filament\Actions\Action::make('saveAsCustomer')
                ->label('Guardar como Cliente')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn() => !$this->record->customer_id)
                ->url(fn() => \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('create') . '?' . http_build_query([
                    'name' => $this->record->customer_name,
                    'email' => $this->record->customer_email,
                    'phone' => $this->record->customer_phone,
                    'lead_id' => $this->record->id, // Passing lead_id might be useful
                ])),
            DeleteAction::make(),
        ];
    }
}
