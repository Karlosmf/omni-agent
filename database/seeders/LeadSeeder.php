<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Lead;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lead::factory(10)
            ->has(
                Booking::factory()
                    ->has(BookingItem::factory()->count(3), 'items')
                    ->has(Transaction::factory()->count(2), 'transactions')
            )
            ->create();

        // Also create some leads without bookings
        Lead::factory(5)->create();
    }
}
