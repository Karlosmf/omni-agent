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
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->string('accent_color')->default('#f59e0b')->after('secondary_color'); // Ambar
            $table->string('neutral_color')->default('#3d4451')->after('accent_color');
            $table->string('base_100_color')->default('#ffffff')->after('neutral_color'); // Fondo
            $table->string('base_200_color')->default('#f2f2f2')->after('base_100_color'); // Superficie
            $table->string('info_color')->default('#3abff8')->after('base_200_color');
            $table->string('success_color')->default('#36d399')->after('info_color');
            $table->string('warning_color')->default('#fbbd23')->after('success_color');
            $table->string('error_color')->default('#f87272')->after('warning_color');
            $table->string('base_content_color')->default('#1f2937')->after('error_color'); // Color de texto principal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn([
                'accent_color', 'neutral_color', 'base_100_color', 'base_200_color',
                'info_color', 'success_color', 'warning_color', 'error_color', 'base_content_color',
            ]);
        });
    }
};
