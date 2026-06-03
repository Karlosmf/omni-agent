<?php

namespace Database\Factories;

use App\Models\AgencySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencySetting>
 */
class AgencySettingFactory extends Factory
{
    protected $model = AgencySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'fe_primary_color' => '#1a56db',
            'fe_secondary_color' => '#7e22ce',
            'fe_accent_color' => '#f59e0b',
            'fe_base_100_color' => '#ffffff',
            'fe_base_200_color' => '#f2f2f2',
            'fe_base_content_color' => '#1f2937',
            'fe_success_color' => '#36d399',
            'fe_error_color' => '#f87272',
            'fe_warning_color' => '#fbbd23',
            'fe_info_color' => '#3abff8',
            'be_primary_color' => '#f59e0b',
            'be_gray_color' => '#71717a',
            'be_info_color' => '#3b82f6',
            'be_success_color' => '#22c55e',
            'be_warning_color' => '#f59e0b',
            'be_danger_color' => '#ef4444',
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'social_links' => [],
        ];
    }
}
