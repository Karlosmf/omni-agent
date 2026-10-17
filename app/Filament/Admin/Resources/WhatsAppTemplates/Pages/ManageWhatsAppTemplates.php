<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplates\Pages;

use App\Filament\Admin\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWhatsAppTemplates extends ManageRecords
{
    protected static string $resource = WhatsAppTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
