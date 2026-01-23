<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer)->get();

        if ($customers->isEmpty()) {
            $this->command->info('No customers found. Skipping BookingSeeder.');

            return;
        }

        foreach ($customers as $customer) {
            // Create 1-3 bookings for each customer
            Booking::factory(rand(1, 3))
                ->for($customer, 'customer')
                ->create();
        }
    }
}
