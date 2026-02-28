<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Vuelo', 'key' => 'flight', 'icon' => 'heroicon-o-paper-airplane'],
            ['name' => 'Hotel', 'key' => 'hotel', 'icon' => 'heroicon-o-building-office'],
            ['name' => 'Hotel y Traslado', 'key' => 'hotel_transfer', 'icon' => 'heroicon-o-building-office-2'],
            ['name' => 'Traslado', 'key' => 'transfer', 'icon' => 'heroicon-o-truck'],
            ['name' => 'Terrestre', 'key' => 'land', 'icon' => 'heroicon-o-map'],
            ['name' => 'Asistencia al viajero', 'key' => 'assistance', 'icon' => 'heroicon-o-shield-check'],
            ['name' => 'Bus', 'key' => 'bus', 'icon' => 'heroicon-o-truck'],
            ['name' => 'Crucero', 'key' => 'cruise', 'icon' => 'heroicon-o-paper-airplane'],
            ['name' => 'Otro', 'key' => 'other', 'icon' => 'heroicon-o-sparkles'],
        ];

        foreach ($types as $type) {
            \App\Models\ServiceType::firstOrCreate(
                ['key' => $type['key']],
                $type
            );
        }
    }
}
