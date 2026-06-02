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
            $table->renameColumn('logo_path', 'logotipo_path');
            $table->renameColumn('favicon_path', 'isotipo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->renameColumn('logotipo_path', 'logo_path');
            $table->renameColumn('isotipo_path', 'favicon_path');
        });
    }
};
