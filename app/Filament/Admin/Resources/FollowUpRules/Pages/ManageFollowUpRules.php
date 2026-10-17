<?php

namespace App\Filament\Admin\Resources\FollowUpRules\Pages;

use App\Filament\Admin\Resources\FollowUpRules\FollowUpRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFollowUpRules extends ManageRecords
{
    protected static string $resource = FollowUpRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
