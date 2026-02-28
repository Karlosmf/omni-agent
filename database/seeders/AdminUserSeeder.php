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
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin,
            ]
        );

        User::updateOrCreate(
            ['email' => 'belenzorzon@luopanviajes.tur.ar'],
            [
                'name' => 'Belen',
                'password' => Hash::make('Agencia843'),
                'role' => UserRole::Admin,
            ]
        );

        User::updateOrCreate(
            ['email' => 'nelaflama@luopanviajes.tur.ar'],
            [
                'name' => 'Nela',
                'password' => Hash::make('Agencia843'),
                'role' => UserRole::Staff,
            ]
        );
    }
}
