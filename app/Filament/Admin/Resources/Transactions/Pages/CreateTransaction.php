<?php

namespace App\Filament\Admin\Resources\Transactions\Pages;

use App\Enums\TransactionType;
use App\Filament\Admin\Resources\Transactions\TransactionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        if (request()->has('booking_id')) {
            $this->form->fill([
                'booking_id' => request()->query('booking_id'),
                'type' => TransactionType::Cobro->value, // Default to Cobro when coming from Booking? Usually yes.
            ]);
        }
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear registro')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
