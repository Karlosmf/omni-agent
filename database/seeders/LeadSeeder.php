<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Lead;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer)->get();

        if ($customers->isEmpty()) {
            return;
        }

        $travelPackages = \App\Models\TravelPackage::all();

        // Create leads with bookings for existing customers
        foreach ($customers->random(20) as $customer) {
            Lead::factory()
                ->for($customer, 'customer')
                ->state(function () use ($travelPackages) {
                    return ['travel_package_id' => $travelPackages->random()->id ?? null];
                })
                ->has(
                    Booking::factory()
                        ->for($customer, 'customer') // Assign same customer to booking
                        ->has(BookingItem::factory()->count(3), 'items')
                        ->has(Transaction::factory()->count(2), 'transactions')
                )
                ->create();
        }

        // Create some loose leads without bookings, also assigned to customers
        Lead::factory(30)
            ->recycle($customers) // Assign random customers from collection
            ->state(function () use ($travelPackages) {
                return ['travel_package_id' => $travelPackages->random()->id ?? null];
            })
            ->create();
    }
}
