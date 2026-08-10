<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('it logs activity when a booking is created', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $booking = Booking::factory()->create();

    $activity = BookingActivity::where('booking_id', $booking->id)
        ->where('type', 'created')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('creado');
    expect($activity->user_id)->toBe($user->id);
});

test('it logs activity when booking status changes', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::Borrador,
    ]);

    $booking->update([
        'status' => BookingStatus::Presupuesto,
    ]);

    $activity = BookingActivity::where('booking_id', $booking->id)
        ->where('type', 'status_changed')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties['old'])->toBe(BookingStatus::Borrador->value);
    expect($activity->properties['new'])->toBe(BookingStatus::Presupuesto->value);
});

test('it logs activity when tracked fields are updated', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $booking = Booking::factory()->create([
        'total_sell' => 1000,
        'destination' => 'Paris',
    ]);

    $booking->update([
        'total_sell' => 1500,
        'destination' => 'Rome',
    ]);

    $activity = BookingActivity::where('booking_id', $booking->id)
        ->where('type', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties['total_sell']['old'])->toEqual(1000);
    expect($activity->properties['total_sell']['new'])->toEqual(1500);
    expect($activity->properties['destination']['old'])->toBe('Paris');
    expect($activity->properties['destination']['new'])->toBe('Rome');
});
