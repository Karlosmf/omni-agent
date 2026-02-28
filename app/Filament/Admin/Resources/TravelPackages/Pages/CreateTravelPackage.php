<?php

namespace App\Filament\Admin\Resources\TravelPackages\Pages;

use App\Filament\Admin\Resources\TravelPackages\TravelPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTravelPackage extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = TravelPackageResource::class;

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
