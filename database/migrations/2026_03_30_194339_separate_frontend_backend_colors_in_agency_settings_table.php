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
            // Renombrar los colores actuales a "fe_" (Frontend)
            $table->renameColumn('primary_color', 'fe_primary_color');
            $table->renameColumn('secondary_color', 'fe_secondary_color');
            $table->renameColumn('accent_color', 'fe_accent_color');
            $table->renameColumn('neutral_color', 'fe_neutral_color');
            $table->renameColumn('base_100_color', 'fe_base_100_color');
            $table->renameColumn('base_200_color', 'fe_base_200_color');
            $table->renameColumn('info_color', 'fe_info_color');
            $table->renameColumn('success_color', 'fe_success_color');
            $table->renameColumn('warning_color', 'fe_warning_color');
            $table->renameColumn('error_color', 'fe_error_color');
            $table->renameColumn('base_content_color', 'fe_base_content_color');

            // Añadir colores específicos para el Panel (Backend)
            $table->string('be_primary_color')->default('#f59e0b')->after('logo_path'); // Amber por defecto
            $table->string('be_success_color')->default('#22c55e')->after('be_primary_color');
            $table->string('be_warning_color')->default('#f59e0b')->after('be_success_color');
            $table->string('be_danger_color')->default('#ef4444')->after('be_warning_color');
            $table->string('be_info_color')->default('#3b82f6')->after('be_danger_color');
            $table->string('be_gray_color')->default('#71717a')->after('be_info_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->renameColumn('fe_primary_color', 'primary_color');
            $table->renameColumn('fe_secondary_color', 'secondary_color');
            $table->renameColumn('fe_accent_color', 'accent_color');
            $table->renameColumn('fe_neutral_color', 'neutral_color');
            $table->renameColumn('fe_base_100_color', 'base_100_color');
            $table->renameColumn('fe_base_200_color', 'base_200_color');
            $table->renameColumn('fe_info_color', 'info_color');
            $table->renameColumn('fe_success_color', 'success_color');
            $table->renameColumn('fe_warning_color', 'warning_color');
            $table->renameColumn('fe_error_color', 'error_color');
            $table->renameColumn('fe_base_content_color', 'base_content_color');

            $table->dropColumn([
                'be_primary_color', 'be_success_color', 'be_warning_color', 
                'be_danger_color', 'be_info_color', 'be_gray_color'
            ]);
        });
    }
};
