<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    public function run(): void
    {
        FinancialAccount::create([
            'name' => 'Caja Pesos',
            'currency' => Currency::ARS,
            'balance' => 0,
            'is_active' => true,
        ]);

        FinancialAccount::create([
            'name' => 'Caja Dólares',
            'currency' => Currency::USD,
            'balance' => 0,
            'is_active' => true,
        ]);

        FinancialAccount::create([
            'name' => 'Banco Galicia (ARS)',
            'currency' => Currency::ARS,
            'balance' => 0,
            'cbu' => '0070001720000001234567',
            'is_active' => true,
        ]);

        FinancialAccount::create([
            'name' => 'Wise (USD)',
            'currency' => Currency::USD,
            'balance' => 0,
            'is_active' => true,
        ]);
    }
}
