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
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('destination');
            $table->integer('nights');
            $table->json('tags')->nullable();
            $table->decimal('price_from', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('services')->nullable();
            $table->text('included')->nullable();
            $table->text('excluded')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_packages');
    }
};
