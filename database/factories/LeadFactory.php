<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
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
            'status' => fake()->randomElement(LeadStatus::cases()),
            'customer_id' => User::factory(['role' => UserRole::Customer]),
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
