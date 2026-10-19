<?php

namespace Database\Factories;

use App\Enums\Channel;
use App\Models\ChannelAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelAccount>
 */
class ChannelAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => Channel::Instagram,
            'label' => fake()->words(2, true),
            'external_id' => (string) fake()->unique()->numerify('##############'),
            'access_token' => 'token-'.fake()->uuid(),
            'app_secret' => 'secret-'.fake()->uuid(),
            'verify_token' => 'verify-'.fake()->uuid(),
            'is_active' => true,
        ];
    }

    public function channel(Channel $channel): static
    {
        return $this->state(['channel' => $channel]);
    }
}
