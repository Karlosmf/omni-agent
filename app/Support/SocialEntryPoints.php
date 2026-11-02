<?php

namespace App\Support;

/**
 * What a customer can tap when opening an Instagram or Messenger chat, and what we store as their message
 * when they do. Meta allows 4 Instagram ice breakers; Messenger shows the same four in its persistent menu.
 */
class SocialEntryPoints
{
    public const GET_STARTED = 'GET_STARTED';

    /**
     * @var array<string, array{question: string, text: string}>
     */
    private const OPTIONS = [
        'COTIZAR' => ['question' => 'Quiero cotizar un viaje', 'text' => 'Hola, quiero cotizar un viaje'],
        'SALIDAS_GRUPALES' => ['question' => 'Salidas grupales', 'text' => 'Hola, quiero info sobre las salidas grupales'],
        'CRUCEROS' => ['question' => 'Cruceros', 'text' => 'Hola, quiero info sobre cruceros'],
        'A_MEDIDA' => ['question' => 'Viaje a medida o luna de miel', 'text' => 'Hola, quiero un viaje a medida'],
    ];

    /**
     * @return list<array{question: string, payload: string}>
     */
    public static function questions(): array
    {
        return collect(self::OPTIONS)
            ->map(fn (array $option, string $payload) => ['question' => $option['question'], 'payload' => $payload])
            ->values()
            ->all();
    }

    /**
     * The message that opens the conversation for a tapped payload; null for payloads we did not set.
     */
    public static function textFor(string $payload): ?string
    {
        if ($payload === self::GET_STARTED) {
            return self::OPTIONS['COTIZAR']['text'];
        }

        return self::OPTIONS[$payload]['text'] ?? null;
    }
}
