<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::factory(10)->create();

        // Add some common suppliers
        Supplier::create([
            'name' => 'Aero Regional',
            'contact_name' => 'Ventas Aero',
            'contact_email' => 'ventas@aeroregional.com',
            'contact_phone' => '011 1234-5678',
            'service_type_id' => 1, // Flight
            'location' => 'Buenos Aires',
        ]);

        Supplier::create([
            'name' => 'Hotel Plaza Roma',
            'contact_name' => 'Reservas',
            'contact_email' => 'reservas@plazaroma.it',
            'contact_phone' => '+39 06 123456',
            'service_type_id' => 2, // Hotel
            'location' => 'Roma',
        ]);
    }
}
