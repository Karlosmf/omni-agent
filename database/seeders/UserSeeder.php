<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // El administrador se crea en AdminUserSeeder
        // Aquí creamos usuarios con rol de cliente
        User::factory(20)->create([
            'role' => UserRole::Customer,
        ]);
    }
}
