<?php

use App\Enums\Currency;
use App\Enums\TransactionType;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->enum('currency', array_column(Currency::cases(), 'value'));
            $table->decimal('amount', 10, 2);
            $table->decimal('exchange_rate', 8, 4);
            $table->decimal('amount_usd_fixed', 10, 2);
            $table->string('method');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
