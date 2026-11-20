<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Words that hand the conversation to a person before the AI is asked. Empty (the default) changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->text('bot_handoff_keywords')->nullable()->after('bot_handoff_rules');
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn('bot_handoff_keywords');
        });
    }
};
