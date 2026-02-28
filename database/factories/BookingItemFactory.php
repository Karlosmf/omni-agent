<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingItem>
 */
class BookingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 100, 1000);
        $sell = $cost + fake()->randomFloat(2, 50, 300);

        return [
            'booking_id' => Booking::factory(),
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'service_type_id' => ServiceType::inRandomOrder()->first()?->id ?? 1,
            'description' => fake()->sentence(),
            'cost' => $cost,
            'sell' => $sell,
        ];
    }
}
