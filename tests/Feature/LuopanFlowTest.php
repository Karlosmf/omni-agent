<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;

it('can verify the full Luopan Flow', function () {
    // 1. Create a Lead
    $lead = Lead::factory()->create([
        'customer_name' => 'Juan Perez',
        'customer_phone' => '123456789',
        'customer_email' => 'juan@example.com',
        'customer_budget' => 'USD 3.000',
        'status' => LeadStatus::New,
    ]);

    expect($lead->status)->toBe(LeadStatus::New);

    // 2. Convert Lead to Customer
    $customer = Customer::create([
        'name' => $lead->customer_name,
        'phone' => $lead->customer_phone,
        'email' => $lead->customer_email,
    ]);

    $lead->update([
        'customer_id' => $customer->id,
        'status' => LeadStatus::Closed,
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($lead->fresh()->customer_id)->toBe($customer->id)
        ->and($lead->fresh()->status)->toBe(LeadStatus::Closed);

    // 3. Create a Booking (starts as Borrador — unified entity replaces Quotation)
    $booking = Booking::create([
        'customer_id' => $customer->id,
        'lead_id' => $lead->id,
        'holder_name' => $customer->name,
        'destination' => 'Cancun',
        'travel_date' => now()->addMonth(),
        'nights' => 7,
        'passengers' => 2,
        'status' => BookingStatus::Borrador,
        'total_cost' => 1000,
        'total_sell' => 1500,
        'profit' => 500,
    ]);

    expect($booking->file_number)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatus::Borrador);

    // 4. Add items to the Booking
    $booking->items()->create([
        'service_type' => 'hotel',
        'description' => 'Hotel 5 Stars',
        'cost' => 800,
        'sell' => 1200,
    ]);

    $booking->items()->create([
        'service_type' => 'transfer',
        'description' => 'Transfer Airport',
        'cost' => 200,
        'sell' => 300,
    ]);

    expect($booking->items->count())->toBe(2);

    // 5. Promote to Presupuesto (sent to client)
    $booking->update(['status' => BookingStatus::Presupuesto]);
    expect($booking->fresh()->status)->toBe(BookingStatus::Presupuesto);

    // 6. Client approves → Señado
    $booking->update(['status' => BookingStatus::Senado]);
    expect($booking->fresh()->status)->toBe(BookingStatus::Senado);

    // 7. Services emitted → Emitido (File activo)
    $booking->update(['status' => BookingStatus::Emitido]);
    expect($booking->fresh()->status)->toBe(BookingStatus::Emitido)
        ->and($booking->customer_id)->toBe($customer->id);
});
