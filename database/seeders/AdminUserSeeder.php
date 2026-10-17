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
        // `role` is not mass assignable, so seed with the guard lifted.
        User::unguarded(function () {
            User::updateOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make('admin123'),
                    'role' => UserRole::Admin,
                ]
            );

            User::updateOrCreate(
                ['email' => 'belenzorzon@omniagent.com'],
                [
                    'name' => 'Belen',
                    'password' => Hash::make('Agencia843'),
                    'role' => UserRole::Admin,
                ]
            );

            User::updateOrCreate(
                ['email' => 'nelaflama@omniagent.com'],
                [
                    'name' => 'Nela',
                    'password' => Hash::make('Agencia843'),
                    'role' => UserRole::Staff,
                ]
            );
        });
    }
}
