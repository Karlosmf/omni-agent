<?php

use App\Enums\LeadStatus;
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
            $table->foreignId('travel_package_id')->nullable()->constrained('travel_packages')->nullOnDelete();
            $table->string('source');
            $table->enum('status', array_column(LeadStatus::cases(), 'value'));
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('customer_budget')->nullable();
            $table->text('raw_message');
            $table->json('ai_data')->nullable();
            $table->text('ai_summary')->nullable();
            $table->boolean('needs_human_attention')->default(false);
            $table->timestamps();

            $table->index('customer_phone');
            $table->index('customer_email');
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
