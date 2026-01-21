<?php

namespace App\Filament\Admin\Resources\FinancialAccounts\Pages;

use App\Filament\Admin\Resources\FinancialAccounts\FinancialAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialAccount extends EditRecord
{
    protected static string $resource = FinancialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
