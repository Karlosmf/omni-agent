<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where an outgoing message was written when it was not the panel or the AI: "phone" is the
     * WhatsApp Business app on the agency's phone (coexistence).
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sent_from', 20)->nullable()->after('sent_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('sent_from');
        });
    }
};
