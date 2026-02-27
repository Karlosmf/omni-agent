<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'service_type' => fake()->randomElement(['hotel', 'transport', 'activity', 'other']),
            'location' => fake()->city(),
            'cuit' => fake()->numerify('##-########-#'),
            'bank_name' => fake()->company() . ' Bank',
            'cbu' => fake()->numerify('######################'),
            'alias' => fake()->word() . '.' . fake()->word() . '.' . fake()->word(),
            'account_number' => fake()->bankAccountNumber(),
        ];
    }
}
