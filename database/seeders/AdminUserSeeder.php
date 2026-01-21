<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        User::firstOrCreate(
            ['email' => 'belenzorzon@luopanviajes.tur.ar'],
            [
                'name' => 'Belen',
                'password' => Hash::make('Agencia843'),
                'role' => UserRole::Admin,
            ]
        );

        User::firstOrCreate(
            ['email' => 'nelaflama@luopanviajes.tur.ar'],
            [
                'name' => 'Nela',
                'password' => Hash::make('Agencia843'),
                'role' => UserRole::Staff, // Assuming Staff role exists, else use standard role
            ]
        );
    }
}
