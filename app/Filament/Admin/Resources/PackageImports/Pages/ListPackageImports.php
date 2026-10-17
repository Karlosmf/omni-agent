<?php

namespace App\Filament\Admin\Resources\PackageImports\Pages;

use App\Filament\Admin\Resources\PackageImports\PackageImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackageImports extends ListRecords
{
    protected static string $resource = PackageImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
