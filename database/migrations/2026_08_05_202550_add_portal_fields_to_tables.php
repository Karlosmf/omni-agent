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
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('vouchers')->nullable()->after('notes');
        });

        Schema::table('booking_passengers', function (Blueprint $table) {
            $table->string('passport_path')->nullable()->after('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('vouchers');
        });

        Schema::table('booking_passengers', function (Blueprint $table) {
            $table->dropColumn('passport_path');
        });
    }
};
