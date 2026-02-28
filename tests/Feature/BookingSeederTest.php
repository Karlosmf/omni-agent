<?php

use App\Models\Booking;
use Database\Seeders\BookingSeeder;

test('booking seeder creates bookings for existing customers', function () {
    // Create some customers
    $customers = \App\Models\User::factory(3)->create(['role' => \App\Enums\UserRole::Customer]);

    // Ensure no bookings exist initially
    Booking::query()->delete();

    // Run the seeder
    $this->seed(BookingSeeder::class);

    // Verify bookings were created
    expect(Booking::count())->toBeGreaterThanOrEqual(3);

    // Verify bookings belong to the created customers
    $customerIds = $customers->pluck('id');
    $bookings = Booking::whereIn('customer_id', $customerIds)->get();

    expect($bookings->count())->toBeGreaterThan(0);

    foreach ($bookings as $booking) {
        expect($customerIds)->toContain($booking->customer_id);
    }
});
