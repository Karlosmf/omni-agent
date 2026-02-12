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
            // Change status from enum to string to support new values
            $table->string('status', 20)->default('borrador')->change();

            // Add quotation-specific fields
            $table->string('destination')->nullable()->after('holder_name');
            $table->unsignedInteger('nights')->nullable()->after('destination');
            $table->unsignedInteger('passengers')->nullable()->after('nights');
            $table->date('valid_until')->nullable()->after('travel_date');
            $table->text('notes')->nullable()->after('internal_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['destination', 'nights', 'passengers', 'valid_until', 'notes']);
        });
    }
};
