<?php

namespace Database\Factories;

use App\Models\ServiceType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
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
            'contact_phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'service_type_id' => ServiceType::inRandomOrder()->first()?->id ?? 1,
            'category' => fake()->word(),
            'location' => fake()->city(),
            'cuit' => fake()->numerify('##-########-#'),
            'bank_name' => fake()->company().' Bank',
            'cbu' => fake()->numerify('######################'),
            'alias' => fake()->word().'.'.fake()->word().'.'.fake()->word(),
            'account_number' => fake()->bankAccountNumber(),
        ];
    }
}
