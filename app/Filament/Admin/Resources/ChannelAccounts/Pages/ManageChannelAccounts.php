<?php

namespace App\Filament\Admin\Resources\ChannelAccounts\Pages;

use App\Filament\Admin\Resources\ChannelAccounts\ChannelAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageChannelAccounts extends ManageRecords
{
    protected static string $resource = ChannelAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
