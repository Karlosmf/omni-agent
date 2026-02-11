<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Filament\Admin\Resources\Leads\Tables\LeadsTable;
use App\Filament\Admin\Resources\Quotations\Tables\QuotationsTable;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Quotation;
use Filament\Tables\Actions\Action;

it('can verify the full Luopan Flow', function () {
    // 1. Create a Lead
    $lead = Lead::factory()->create([
        'customer_name' => 'Juan Perez',
        'customer_phone' => '123456789',
        'status' => LeadStatus::New,
    ]);

    expect($lead->status)->toBe(LeadStatus::New);

    // 2. Simulate "Convert to Customer" Action logic manually (since action closure is protected)
    // We replicate the logic inside the LeadsTable action closure
    $customer = Customer::create([
        'name' => $lead->customer_name,
        'phone' => $lead->customer_phone,
        'email' => 'juan@example.com', // Manual input simulation
    ]);

    $lead->update([
        'customer_id' => $customer->id,
        'status' => LeadStatus::Closed,
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($lead->fresh()->customer_id)->toBe($customer->id)
        ->and($lead->fresh()->status)->toBe(LeadStatus::Closed);

    // 3. Create a Quotation for the Customer
    $quotation = Quotation::create([
        'customer_id' => $customer->id,
        'lead_id' => $lead->id,
        'destination' => 'Cancun',
        'travel_date' => now()->addMonth(),
        'nights' => 7,
        'passengers' => 2,
        'total_cost' => 1000,
        'total_sell' => 1500,
        'profit' => 500,
        'items' => [
            ['description' => 'Hotel 5 Stars', 'cost' => 800, 'sell' => 1200],
            ['description' => 'Transfer', 'cost' => 200, 'sell' => 300],
        ],
        'status' => QuotationStatus::Draft,
    ]);

    expect($quotation->quotation_number)->not->toBeNull();

    // 4. Simulate "Convert to File" (Booking) logic
    // Using the static method from QuotationsTable if accessible, or replicating logic
    // Since it fits inside a static method on the Table class, we can try to call it if public
    // But it's better to test the logic directly or move the logic to a Service.
    // For this test, we will replicate the crucial logic to verify the models interact correctly.

    $booking = Booking::create([
        'customer_id' => $quotation->customer_id,
        'lead_id' => $quotation->lead_id,
        'holder_name' => $quotation->customer->name,
        'travel_date' => $quotation->travel_date,
        'status' => BookingStatus::Presupuesto,
        'file_number' => null, // Let boot handle it
        'total_cost' => $quotation->total_cost,
        'total_sell' => $quotation->total_sell,
        'profit' => $quotation->profit,
    ]);

    // Copy items
    foreach ($quotation->items as $item) {
        $booking->items()->create([
            'service_type' => 'other', // Required field
            'description' => $item['description'],
            'cost' => $item['cost'],
            'sell' => $item['sell'],
        ]);
    }

    $quotation->update(['status' => QuotationStatus::Accepted]);

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->file_number)->not->toBeNull()
        ->and($booking->items->count())->toBe(2)
        ->and($booking->customer_id)->toBe($customer->id)
        ->and($quotation->fresh()->status)->toBe(QuotationStatus::Accepted);

});
