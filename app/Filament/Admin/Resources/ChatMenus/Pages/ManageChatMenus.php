<?php

namespace App\Filament\Admin\Resources\ChatMenus\Pages;

use App\Filament\Admin\Resources\ChatMenus\ChatMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageChatMenus extends ManageRecords
{
    protected static string $resource = ChatMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
