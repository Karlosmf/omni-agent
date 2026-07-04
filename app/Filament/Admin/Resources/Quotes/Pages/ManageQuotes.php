<?php

namespace App\Filament\Admin\Resources\Quotes\Pages;

use App\Filament\Admin\Resources\Quotes\QuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuotes extends ManageRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
