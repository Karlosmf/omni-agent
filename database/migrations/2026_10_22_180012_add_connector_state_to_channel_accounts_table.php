<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            // How the token was issued decides the Graph host: Instagram login talks to graph.instagram.com.
            $table->string('auth_type', 30)->default('facebook_login');
            $table->string('page_id')->nullable();
            $table->string('status', 30)->default('connected');
            $table->text('last_error')->nullable();
            $table->boolean('allow_human_agent')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn(['auth_type', 'page_id', 'status', 'last_error', 'allow_human_agent']);
        });
    }
};
