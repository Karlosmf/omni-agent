<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 500, 5000);
        $sell = $cost + fake()->randomFloat(2, 100, 1000);

        return [
            'lead_id' => Lead::factory(),
            'file_number' => 'LP-'.fake()->year().'-'.fake()->unique()->numberBetween(100, 999),
            'holder_name' => fake()->name(),
            'currency' => 'USD',
            'total_cost' => $cost,
            'total_sell' => $sell,
            'profit' => $sell - $cost,
            'status' => fake()->randomElement(BookingStatus::cases()),
            'travel_date' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ];
    }
}
