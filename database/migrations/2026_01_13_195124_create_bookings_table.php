<?php

use App\Enums\BookingStatus;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_number')->unique();
            $table->string('holder_name');
            $table->string('destination')->nullable();
            $table->integer('nights')->nullable()->default(0);
            $table->integer('passengers')->nullable()->default(1);
            $table->dateTime('travel_date')->nullable();
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('total_sell', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->enum('status', array_column(BookingStatus::cases(), 'value'))->default(BookingStatus::Borrador->value);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 15, 2)->default(1.00);
            $table->dateTime('valid_until')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
