<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zernio replies by conversation, not by phone: remember which conversation belongs to each WhatsApp number.
     */
    public function up(): void
    {
        Schema::create('zernio_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('conversation_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zernio_conversations');
    }
};
