<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp list menus a keyword opens (destinations, packages or typed options).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 24);
            $table->text('keywords')->nullable();
            $table->string('source', 20);
            $table->json('options')->nullable();
            $table->string('header', 60)->nullable();
            $table->text('body');
            $table->string('button_text', 20)->nullable();
            $table->string('on_select', 20)->default('bot');
            $table->text('reply_text')->nullable();
            $table->foreignId('next_menu_id')->nullable()->constrained('chat_menus')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_menus');
    }
};
