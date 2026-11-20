<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Widgets\Analytics\TopProductsWidget;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\TravelPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('ranks packages by confirmed bookings and leaves out packages without sales', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    [$bestSeller, $runnerUp, $quoteOnly] = TravelPackage::factory()->count(3)->create();

    $book = fn (TravelPackage $package, BookingStatus $status) => Booking::factory()->create([
        'lead_id' => Lead::factory()->create(['travel_package_id' => $package->id])->id,
        'status' => $status,
    ]);

    $book($bestSeller, BookingStatus::Senado);
    $book($bestSeller, BookingStatus::Emitido);
    $book($runnerUp, BookingStatus::Emitido);
    $book($quoteOnly, BookingStatus::Presupuesto);

    Livewire::test(TopProductsWidget::class)
        ->assertCanSeeTableRecords([$bestSeller, $runnerUp], inOrder: true)
        ->assertCanNotSeeTableRecords([$quoteOnly]);
});
