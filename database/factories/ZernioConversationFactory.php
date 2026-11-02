<?php

namespace Database\Factories;

use App\Models\ZernioConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZernioConversation>
 */
class ZernioConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => '+54911'.fake()->unique()->numerify('########'),
            'conversation_id' => 'conv_'.fake()->unique()->bothify('??##??##'),
        ];
    }
}
