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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('currency')->default('USD')->after('travel_date');
            $table->decimal('exchange_rate', 15, 2)->default(1.00)->after('currency');

            $table->renameColumn('total_cost_usd', 'total_cost');
            $table->renameColumn('total_sell_usd', 'total_sell');
            $table->renameColumn('profit_usd', 'profit');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->renameColumn('cost_usd', 'cost');
            $table->renameColumn('sell_usd', 'sell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);

            $table->renameColumn('total_cost', 'total_cost_usd');
            $table->renameColumn('total_sell', 'total_sell_usd');
            $table->renameColumn('profit', 'profit_usd');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->renameColumn('cost', 'cost_usd');
            $table->renameColumn('sell', 'sell_usd');
        });
    }
};
