<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\ServiceType;
use App\Models\TravelPackage;
use App\Models\User;
use App\Services\BudgetGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('clones a travel package with itinerary into a new budget', function () {
    // Arrange
    $customer = User::factory()->create(['name' => 'John Doe', 'role' => UserRole::Customer]);
    ServiceType::firstOrCreate(['key' => 'other'], ['name' => 'Otro']);

    $package = TravelPackage::factory()->create([
        'title' => 'Viaje a Roma',
        'destination' => 'Roma',
        'nights' => 7,
        'currency' => 'USD',
        'price_from' => 1500,
        'description' => 'Descripción del viaje',
        'summary' => 'Resumen del viaje',
        'itinerary' => [
            ['day' => '1', 'title' => 'Llegada', 'description' => 'Llegada a Roma'],
            ['day' => '2', 'title' => 'Tour', 'description' => 'Tour Coliseo'],
        ],
    ]);

    $service = new BudgetGenerationService;

    // Act
    $booking = $service->clonePackageToBudget($package, $customer);

    // Assert
    expect($booking->customer_id)->toBe($customer->id)
        ->and($booking->holder_name)->toBe('John Doe')
        ->and($booking->destination)->toBe('Roma')
        ->and($booking->nights)->toBe(7)
        ->and($booking->passengers)->toBe(2)
        ->and($booking->currency)->toBe('USD')
        ->and((float) $booking->total_cost)->toBe(0.0)
        ->and((float) $booking->total_sell)->toBe(1500.0)
        ->and($booking->status)->toBe(BookingStatus::Borrador)
        ->and($booking->items)->toHaveCount(2);

    expect($booking->items->first()->service_type_id)->toBe(ServiceType::where('key', 'other')->value('id') ?? 1);
});

it('clones package info into an empty general item if itinerary is empty', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    ServiceType::firstOrCreate(['key' => 'other'], ['name' => 'Otro']);
    $package = TravelPackage::factory()->create([
        'itinerary' => null,
        'price_from' => 2000,
    ]);

    $service = new BudgetGenerationService;
    $booking = $service->clonePackageToBudget($package, $customer);

    expect($booking->items)->toHaveCount(1)
        ->and((float) $booking->total_sell)->toBe(2000.0)
        ->and($booking->items->first()->service_type_id)->toBe(ServiceType::where('key', 'other')->value('id') ?? 1);
});
