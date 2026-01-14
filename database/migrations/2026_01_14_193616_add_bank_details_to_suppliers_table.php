<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('cuit')->nullable(); // Identificación fiscal
            $table->string('bank_name')->nullable();
            $table->string('cbu')->nullable(); // Clave Bancaria Uniforme
            $table->string('alias')->nullable();
            $table->string('account_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['cuit', 'bank_name', 'cbu', 'alias', 'account_number']);
        });
    }
};
