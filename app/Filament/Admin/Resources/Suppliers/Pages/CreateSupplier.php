<?php

namespace App\Filament\Admin\Resources\Suppliers\Pages;

use App\Filament\Admin\Resources\Suppliers\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = SupplierResource::class;

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
