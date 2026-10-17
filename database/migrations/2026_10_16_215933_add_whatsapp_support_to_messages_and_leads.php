<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('channel')->default('web')->after('lead_id'); // web | whatsapp
            $table->string('direction')->nullable()->after('channel'); // in | out
            $table->string('external_id')->nullable()->unique()->after('direction');
            $table->string('status')->nullable()->after('external_id'); // sent | delivered | read | failed
            $table->foreignId('sent_by_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('media_path')->nullable()->after('content');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('ai_paused')->default(false);
            $table->timestamp('whatsapp_last_inbound_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('purpose')->unique(); // installment_reminder | quote_ready | follow_up
            $table->string('name'); // exact name approved by Meta
            $table->string('language')->default('es_AR');
            $table->text('body'); // preview, with {{1}}, {{2}}... placeholders
            $table->unsignedTinyInteger('variables_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['ai_paused', 'whatsapp_last_inbound_at', 'unread_count']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sent_by_user_id');
            $table->dropColumn(['channel', 'direction', 'external_id', 'status', 'media_path']);
        });
    }
};
