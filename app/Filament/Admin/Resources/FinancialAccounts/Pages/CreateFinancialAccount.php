<?php

namespace App\Filament\Admin\Resources\FinancialAccounts\Pages;

use App\Filament\Admin\Resources\FinancialAccounts\FinancialAccountResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialAccount extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = FinancialAccountResource::class;

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
