<?php

namespace App\Filament\Admin\Resources\TravelPackages\Pages;

use App\Filament\Admin\Resources\TravelPackages\TravelPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTravelPackages extends ListRecords
{
    protected static string $resource = TravelPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Idea de Viaje'),
        ];
    }
}
