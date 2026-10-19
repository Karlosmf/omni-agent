<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            // Minutes a hot lead may wait for a person before the team is alerted. Null turns the alert off.
            $table->unsignedSmallInteger('sla_first_response_minutes')->nullable()->default(15);
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn('sla_first_response_minutes');
        });
    }
};
