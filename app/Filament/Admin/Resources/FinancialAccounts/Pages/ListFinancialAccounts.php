<?php

namespace App\Filament\Admin\Resources\FinancialAccounts\Pages;

use App\Filament\Admin\Resources\FinancialAccounts\FinancialAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialAccounts extends ListRecords
{
    protected static string $resource = FinancialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
