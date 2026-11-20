<?php

namespace Database\Factories;

use App\Enums\ChatMenuAction;
use App\Enums\ChatMenuSource;
use App\Models\ChatMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMenu>
 */
class ChatMenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Destinos',
            'keywords' => 'destinos, paquetes',
            'source' => ChatMenuSource::Destinations,
            'options' => null,
            'header' => null,
            'body' => '¿A dónde te gustaría viajar? Elegí un destino.',
            'button_text' => 'Ver destinos',
            'on_select' => ChatMenuAction::Bot,
            'reply_text' => null,
            'next_menu_id' => null,
            'is_active' => true,
        ];
    }

    public function packages(): static
    {
        return $this->state(fn () => [
            'name' => 'Paquetes',
            'keywords' => null,
            'source' => ChatMenuSource::Packages,
            'body' => 'Estos son los paquetes que tenemos:',
            'button_text' => 'Ver paquetes',
        ]);
    }

    /**
     * @param  list<array{title: string, description?: string}>  $options
     */
    public function withOptions(array $options): static
    {
        return $this->state(fn () => [
            'name' => 'Opciones',
            'source' => ChatMenuSource::Options,
            'options' => $options,
            'body' => '¿En qué te podemos ayudar?',
            'button_text' => 'Ver opciones',
        ]);
    }

    public function opens(ChatMenu $next): static
    {
        return $this->state(fn () => ['on_select' => ChatMenuAction::Menu, 'next_menu_id' => $next->id]);
    }
}
