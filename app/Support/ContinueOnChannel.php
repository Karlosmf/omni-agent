<?php

namespace App\Support;

use App\Models\Lead;

/**
 * Botones para que el cliente siga la charla de la web en su red. Lo inicia el cliente:
 * Meta solo deja escribirle a quien escribió primero. El código une la conversación con este lead.
 */
class ContinueOnChannel
{
    /**
     * @return list<array{channel: string, label: string, url: string}>
     */
    public static function links(Lead $lead, ?string $origin = null): array
    {
        $code = $lead->ensureChannelLinkCode();
        $links = [];

        foreach ((array) (get_agency_settings()?->social_links ?? []) as $social) {
            $url = (string) ($social['url'] ?? '');

            if ($number = self::whatsappNumber($url)) {
                $text = rawurlencode("¡Hola! Vengo del chat de la web. Mi código es {$code}");
                $links['whatsapp'] = ['channel' => 'whatsapp', 'label' => 'Seguir por WhatsApp', 'url' => "https://wa.me/{$number}?text={$text}"];
            } elseif ($user = self::handle($url, 'instagram.com')) {
                $links['instagram'] = ['channel' => 'instagram', 'label' => 'Seguir por Instagram', 'url' => "https://ig.me/m/{$user}?ref={$code}"];
            } elseif ($page = self::handle($url, 'facebook.com')) {
                $links['facebook'] = ['channel' => 'facebook', 'label' => 'Seguir por Messenger', 'url' => "https://m.me/{$page}?ref={$code}"];
            }
        }

        // Primero la red de la que vino.
        uksort($links, fn (string $a, string $b) => ($b === $origin) <=> ($a === $origin));

        return array_values($links);
    }

    private static function whatsappNumber(string $url): ?string
    {
        if (preg_match('#wa\.me/(\d{6,15})#', $url, $m) || preg_match('#whatsapp\.com/send\?phone=\+?(\d{6,15})#', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    private static function handle(string $url, string $domain): ?string
    {
        if (! str_contains($url, $domain)) {
            return null;
        }

        $first = explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/'))[0] ?? '';

        return preg_match('/^[A-Za-z0-9._-]{2,50}$/', $first) && ! in_array($first, ['profile.php', 'pages', 'p'], true) ? $first : null;
    }
}
