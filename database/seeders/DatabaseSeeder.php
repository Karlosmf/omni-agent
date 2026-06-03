<?php

namespace Database\Seeders;

use App\Models\TravelPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AgencySettingSeeder::class,
            ServiceTypeSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            FinancialAccountSeeder::class,
            TransactionCategorySeeder::class,
            SupplierSeeder::class,
        ]);

        TravelPackage::factory(10)->create();

        $this->call([
            LeadSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
