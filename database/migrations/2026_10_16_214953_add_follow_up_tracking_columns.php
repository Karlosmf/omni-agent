<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->boolean('auto_assign_leads')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn('auto_assign_leads');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['last_contacted_at', 'next_follow_up_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['first_viewed_at', 'last_viewed_at', 'view_count']);
        });
    }
};
