<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Tokens the provider reported for the AI reply stored in this row (null for human and customer messages).
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->string('ai_model')->nullable();
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            // AI replies the agency allows per calendar month. Null = no limit.
            $table->unsignedInteger('ai_monthly_message_limit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn('ai_monthly_message_limit');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['input_tokens', 'output_tokens', 'ai_model']);
        });
    }
};
