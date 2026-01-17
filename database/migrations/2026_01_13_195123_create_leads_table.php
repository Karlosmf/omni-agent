<?php

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source');
            $table->enum('temperature', array_column(LeadTemperature::cases(), 'value'));
            $table->enum('status', array_column(LeadStatus::cases(), 'value'));
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('raw_message');
            $table->json('ai_data')->nullable();
            $table->text('ai_summary')->nullable();
            $table->boolean('needs_human_attention')->default(false);
            $table->timestamps();

            $table->index('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};