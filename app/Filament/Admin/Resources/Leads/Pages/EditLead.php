<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Filament\Admin\Resources\Leads\LeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('convert_to_customer')
                ->label('Convertir a Cliente')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(fn (\App\Models\Lead $record) => \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('create', [
                    'name' => $record->customer_name,
                    'phone' => $record->customer_phone,
                    'email' => $record->customer_email,
                    'lead_id' => $record->id,
                ]))
                ->visible(fn () => ! $this->record->customer_id),
            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
