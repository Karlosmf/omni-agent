<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number'); // 0 = seña
            $table->string('label');
            $table->date('due_date');
            $table->decimal('amount', 15, 2); // always USD, like the booking
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->json('reminders_sent')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'number']);
            $table->index(['status', 'due_date']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('installment_id')->nullable()->after('booking_id')
                ->constrained('payment_installments')->nullOnDelete();
        });

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('installments_last_due_days_before_trip')->default(30);
            $table->text('payment_instructions')->nullable();
            $table->text('reminder_before_text')->nullable();
            $table->text('reminder_due_text')->nullable();
            $table->text('reminder_overdue_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn(['installments_last_due_days_before_trip', 'payment_instructions', 'reminder_before_text', 'reminder_due_text', 'reminder_overdue_text']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('installment_id');
        });

        Schema::dropIfExists('payment_installments');
    }
};
