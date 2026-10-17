<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_type'); // url | text | pdf
            $table->text('source_url')->nullable();
            $table->string('source_file')->nullable();
            $table->longText('source_text')->nullable();
            $table->boolean('use_source_images')->default(false);
            $table->string('status')->default('queued');
            $table->longText('extracted_text')->nullable();
            $table->json('ai_payload')->nullable();
            $table->json('image_candidates')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('travel_package_id')->nullable()->constrained('travel_packages')->nullOnDelete();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('travel_packages', function (Blueprint $table) {
            $table->json('image_credits')->nullable();
            $table->unsignedBigInteger('source_import_id')->nullable();
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->text('unsplash_access_key')->nullable();
            $table->text('pexels_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn(['unsplash_access_key', 'pexels_api_key']);
        });

        Schema::table('travel_packages', function (Blueprint $table) {
            $table->dropColumn(['image_credits', 'source_import_id']);
        });

        Schema::dropIfExists('package_imports');
    }
};
