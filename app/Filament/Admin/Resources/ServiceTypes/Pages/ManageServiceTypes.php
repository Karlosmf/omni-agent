<?php

namespace App\Filament\Admin\Resources\ServiceTypes\Pages;

use App\Filament\Admin\Resources\ServiceTypes\ServiceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceTypes extends ManageRecords
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo Tipo de Servicio'),
        ];
    }
}
