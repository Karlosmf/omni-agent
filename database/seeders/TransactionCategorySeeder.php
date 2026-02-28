<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ingresos
        TransactionCategory::create(['name' => 'Pago de Pasajero', 'type' => 'ingreso', 'is_system' => true]);
        TransactionCategory::create(['name' => 'Devolución de Proveedor', 'type' => 'ingreso', 'is_system' => true]);
        TransactionCategory::create(['name' => 'Ajuste de Saldo (+)', 'type' => 'ingreso', 'is_system' => true]);

        // Egresos
        TransactionCategory::create(['name' => 'Pago a Proveedor', 'type' => 'egreso', 'is_system' => true]);
        TransactionCategory::create(['name' => 'Gastos Administrativos', 'type' => 'egreso', 'is_system' => true]);
        TransactionCategory::create(['name' => 'Publicidad', 'type' => 'egreso', 'is_system' => true]);
        TransactionCategory::create(['name' => 'Ajuste de Saldo (-)', 'type' => 'egreso', 'is_system' => true]);
    }
}
