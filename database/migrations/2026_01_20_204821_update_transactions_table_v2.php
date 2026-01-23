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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            // supplier_id already exists in original migration
            $table->string('attachment_path')->nullable();
            $table->nullableMorphs('payable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['financial_account_id']);
            $table->dropForeign(['transaction_category_id']);
            $table->dropColumn(['financial_account_id', 'transaction_category_id', 'attachment_path', 'payable_type', 'payable_id']);
        });
    }
};
