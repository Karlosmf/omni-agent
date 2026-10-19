<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // whatsapp | instagram | messenger
            $table->string('label'); // "Ventas", "Soporte"...
            $table->string('external_id'); // phone_number_id | page id | instagram account id
            $table->text('access_token')->nullable(); // encrypted
            $table->text('app_secret')->nullable(); // encrypted
            $table->string('verify_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel', 'external_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('source');
            $table->string('channel_user_id')->nullable()->after('channel');
            $table->foreignId('channel_account_id')->nullable()->after('channel_user_id')->constrained('channel_accounts')->nullOnDelete();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('sla_alerted_at')->nullable();
            $table->json('attribution')->nullable();

            $table->index(['channel', 'channel_user_id']);
        });

        // Existing WhatsApp conversations keep working: copy their window into the generic column.
        DB::table('leads')->where('source', 'whatsapp')->update([
            'channel' => 'whatsapp',
            'last_inbound_at' => DB::raw('whatsapp_last_inbound_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['channel', 'channel_user_id']);
            $table->dropConstrainedForeignId('channel_account_id');
            $table->dropColumn(['channel', 'channel_user_id', 'last_inbound_at', 'first_response_at', 'sla_alerted_at', 'attribution']);
        });

        Schema::dropIfExists('channel_accounts');
    }
};
