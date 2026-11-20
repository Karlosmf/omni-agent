<?php

namespace Database\Factories;

use App\Enums\BotNoticeType;
use App\Models\BotNotice;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotNotice>
 */
class BotNoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'type' => fake()->randomElement(BotNoticeType::cases()),
            'detail' => fake()->sentence(),
        ];
    }
}
