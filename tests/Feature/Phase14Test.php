<?php

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it auto generates file number correctly', function () {
    $year = now()->year;

    // Create first booking
    $booking1 = Booking::factory()->create([
        'file_number' => null,
    ]);

    expect($booking1->file_number)->toBe("LPN-{$year}-00001");

    // Create second booking
    $booking2 = Booking::factory()->create([
        'file_number' => null,
    ]);

    expect($booking2->file_number)->toBe("LPN-{$year}-00002");

    // Create booking with explicit number
    $booking3 = Booking::factory()->create([
        'file_number' => 'CUSTOM-123',
    ]);
    expect($booking3->file_number)->toBe('CUSTOM-123');

    // Create fourth booking, should resume from max of correct pattern
    $booking4 = Booking::factory()->create([
        'file_number' => null,
    ]);
    expect($booking4->file_number)->toBe("LPN-{$year}-00003");
});
