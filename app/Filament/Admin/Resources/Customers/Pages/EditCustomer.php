<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create_budget')
                ->label('Crear Presupuesto')
                ->icon('heroicon-o-document-currency-dollar')
                ->url(fn (\App\Models\Customer $customer) => \App\Filament\Admin\Resources\Quotations\QuotationResource::getUrl('create', ['customer_id' => $customer->id])),
            DeleteAction::make(),
        ];
    }
}
