<?php

namespace Database\Seeders;

use App\Models\TravelPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración: clientes, leads y expedientes inventados. Nunca en producción.
 * Uso: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            SupplierSeeder::class,
        ]);

        TravelPackage::factory(10)->create();

        $this->call([
            LeadSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
