<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger');
            $table->unsignedInteger('delay_hours')->default(0);
            $table->string('action');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('follow_up_rules')->insert([
            ['name' => 'Lead nuevo sin agente', 'trigger' => 'lead_unassigned', 'delay_hours' => 2, 'action' => 'notify_admins'],
            ['name' => 'Lead sin contactar', 'trigger' => 'lead_not_contacted', 'delay_hours' => 24, 'action' => 'create_task'],
            ['name' => 'Lead pide un humano', 'trigger' => 'lead_hot', 'delay_hours' => 0, 'action' => 'create_task'],
            ['name' => 'Presupuesto enviado y no abierto', 'trigger' => 'booking_not_opened', 'delay_hours' => 48, 'action' => 'create_task'],
            ['name' => 'Presupuesto abierto 3 veces sin señar', 'trigger' => 'booking_viewed_often', 'delay_hours' => 24, 'action' => 'create_task'],
            ['name' => 'Presupuesto vencido', 'trigger' => 'booking_expired', 'delay_hours' => 0, 'action' => 'notify_agent'],
        ]);

        DB::table('follow_up_rules')->update(['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_rules');
    }
};
