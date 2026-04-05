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
            $table->string('ai_assistant_name')->default('Brisa')->after('company_name');
            $table->text('hero_cta_url')->nullable()->after('address');
            $table->text('google_maps_url')->nullable()->after('hero_cta_url');
            $table->text('meta_description')->nullable()->after('google_maps_url');
            $table->text('footer_text')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_assistant_name',
                'hero_cta_url',
                'google_maps_url',
                'meta_description',
                'footer_text',
            ]);
        });
    }
};
