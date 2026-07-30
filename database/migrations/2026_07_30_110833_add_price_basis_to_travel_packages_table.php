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
        Schema::table('travel_packages', function (Blueprint $table) {
            // The price basis indicates the occupancy type the price is based on.
            // Values: 'por_persona', 'base_doble', 'por_persona_base_doble', 'base_triple', 'base_cuadruple'
            $table->string('price_basis')->default('por_persona')->after('price_from');

            // Minimum number of passengers that must be billed according to the selected basis.
            $table->unsignedTinyInteger('price_basis_min')->default(1)->after('price_basis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_packages', function (Blueprint $table) {
            $table->dropColumn(['price_basis', 'price_basis_min']);
        });
    }
};
