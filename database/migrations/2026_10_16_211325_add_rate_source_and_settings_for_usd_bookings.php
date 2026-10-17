<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            // Units of `currency` per 1 USD. Null means "not quoted"; never assumed 1:1 for non-USD items.
            $table->decimal('exchange_rate', 15, 4)->nullable()->default(null)->change();
            $table->string('rate_source')->nullable()->after('exchange_rate');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('rate_source')->nullable()->after('exchange_rate');
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->string('default_rate_source')->default('oficial');
            $table->string('file_number_prefix', 12)->default('LPN');
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn(['default_rate_source', 'file_number_prefix']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('rate_source');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn('rate_source');
            $table->decimal('exchange_rate', 15, 2)->default(1.00)->change();
        });
    }
};
