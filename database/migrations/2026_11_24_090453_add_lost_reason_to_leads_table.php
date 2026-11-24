<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lost_reason', 30)->nullable()->after('status');
            $table->string('lost_note', 500)->nullable()->after('lost_reason');
            $table->timestamp('lost_at')->nullable()->after('lost_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['lost_reason', 'lost_note', 'lost_at']);
        });
    }
};
