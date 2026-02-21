<?php

namespace Database\Seeders;

use App\Models\TravelPackage;
use Illuminate\Database\Seeder;

class TravelPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TravelPackage::factory()->count(5)->create();
    }
}
