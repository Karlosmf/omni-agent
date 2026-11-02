<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Where the web visit came from (UTM or the in-app browser). Never a person's identity.
            $table->string('utm_source', 30)->nullable();
            $table->string('utm_medium', 60)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            // One-time code that lets the customer continue this web chat on WhatsApp, Instagram or Messenger.
            $table->string('channel_link_code', 12)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['channel_link_code']);
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'channel_link_code']);
        });
    }
};
