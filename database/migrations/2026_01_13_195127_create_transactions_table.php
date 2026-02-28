<?php

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
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_account_id')->nullable()->constrained('supplier_accounts')->nullOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->string('payer_name')->nullable();
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->decimal('amount', 15, 2);
            $table->string('currency');
            $table->decimal('exchange_rate', 15, 4)->nullable();
            $table->decimal('amount_usd_fixed', 15, 2)->nullable();
            $table->dateTime('date')->nullable();
            $table->string('method');
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->string('attachment_path')->nullable();
            $table->nullableMorphs('payable');
            $table->json('tax_details')->nullable();
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
