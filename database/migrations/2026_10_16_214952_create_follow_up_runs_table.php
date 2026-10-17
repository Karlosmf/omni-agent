<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per (rule, lead/booking): guarantees a rule fires only once per subject.
        Schema::create('follow_up_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_rule_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->unique(['follow_up_rule_id', 'subject_type', 'subject_id'], 'follow_up_runs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_runs');
    }
};
