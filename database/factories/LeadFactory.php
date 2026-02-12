<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(['whatsapp', 'instagram']),
            'temperature' => fake()->randomElement(LeadTemperature::cases()),
            'status' => fake()->randomElement(LeadStatus::cases()),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->optional(0.7)->safeEmail(),
            'customer_budget' => fake()->optional(0.5)->randomElement(['Hasta USD 1.000', 'USD 1.000 - 3.000', 'USD 3.000 - 5.000', 'Más de USD 5.000', 'No definido']),
            'raw_message' => fake()->paragraph(),
            'ai_data' => [
                'destino' => fake()->city(),
                'presupuesto' => fake()->numberBetween(1000, 5000),
                'pasajeros' => fake()->numberBetween(1, 5),
            ],
            'ai_summary' => fake()->sentence(),
            'needs_human_attention' => fake()->boolean(),
        ];
    }
}
