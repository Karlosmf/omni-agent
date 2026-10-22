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
        // Solo para la demo: la contraseña es aleatoria, usá «Olvidé mi contraseña» o omni:install para entrar.
        // `role` is not mass assignable, so seed with the guard lifted.
        User::unguarded(function () {
            User::updateOrCreate(
                ['email' => 'demo@example.com'],
                [
                    'name' => 'Demo',
                    'password' => Hash::make('demo-'.str()->random(12)),
                    'role' => UserRole::Admin,
                ]
            );
        });
    }
}
