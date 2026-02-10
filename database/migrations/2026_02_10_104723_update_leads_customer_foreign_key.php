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
        Schema::table('leads', function (Blueprint $table) {
            // Drop the old constraint pointing to users
            // Note: SQLite might ignore this or require full table rebuild if not supported,
            // but Laravel's schema builder handles it best effort.
            // If constraint name is standard:
            $table->dropForeign(['customer_id']);

            // Add new constraint pointing to customers
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
