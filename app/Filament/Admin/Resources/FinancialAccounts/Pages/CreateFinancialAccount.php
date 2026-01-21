<?php

namespace App\Filament\Admin\Resources\FinancialAccounts\Pages;

use App\Filament\Admin\Resources\FinancialAccounts\FinancialAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialAccount extends CreateRecord
{
    protected static string $resource = FinancialAccountResource::class;
}
