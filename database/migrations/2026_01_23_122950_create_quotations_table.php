<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination');
            $table->date('travel_date')->nullable();
            $table->integer('nights')->nullable();
            $table->integer('passengers')->default(1);
            $table->json('items')->nullable();
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('total_sell', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->string('status')->default('draft');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
