<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Only adds columns (status widens from enum to string): existing data stays as it is and the bot starts switched off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->change();
            $table->json('trip_data')->nullable()->after('ai_data');
            $table->timestamp('ai_paused_until')->nullable()->after('ai_paused');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('traveler_profile')->nullable();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->json('payload')->nullable();
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->boolean('bot_enabled')->default(false);
            $table->text('bot_instructions')->nullable();
            $table->text('bot_handoff_rules')->nullable();
            $table->json('bot_promotions')->nullable();
            $table->json('bot_business_days')->nullable();
            $table->string('bot_hours_from', 5)->nullable();
            $table->string('bot_hours_to', 5)->nullable();
            $table->unsignedSmallInteger('bot_pause_hours')->default(12);
            $table->unsignedSmallInteger('bot_daily_reply_limit')->default(30);
            $table->string('ai_base_url')->nullable();
            $table->decimal('ai_monthly_spend_limit_usd', 10, 2)->nullable();
            $table->decimal('ai_input_price_per_million', 10, 4)->nullable();
            $table->decimal('ai_output_price_per_million', 10, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bot_enabled', 'bot_instructions', 'bot_handoff_rules', 'bot_promotions', 'bot_business_days', 'bot_hours_from',
                'bot_hours_to', 'bot_pause_hours', 'bot_daily_reply_limit', 'ai_base_url', 'ai_monthly_spend_limit_usd',
                'ai_input_price_per_million', 'ai_output_price_per_million',
            ]);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('payload');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('traveler_profile');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['trip_data', 'ai_paused_until']);
        });
    }
};
