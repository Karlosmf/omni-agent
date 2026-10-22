<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Datos base de una instalación de producción. Los datos de prueba están en DemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AgencySettingSeeder::class,
            ServiceTypeSeeder::class,
            FinancialAccountSeeder::class,
            TransactionCategorySeeder::class,
        ]);
    }
}
