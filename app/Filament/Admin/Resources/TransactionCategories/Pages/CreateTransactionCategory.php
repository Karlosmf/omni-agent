<?php

namespace App\Filament\Admin\Resources\TransactionCategories\Pages;

use App\Filament\Admin\Resources\TransactionCategories\TransactionCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionCategory extends CreateRecord
{
    protected static bool $canCreateAnother = false;


    protected static string $resource = TransactionCategoryResource::class;
}
