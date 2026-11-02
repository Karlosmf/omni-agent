<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * De dónde llegó la visita a la web: el parámetro UTM o `from` del enlace, el navegador interno
 * de la red social o el dominio que la mandó. Solo la red, nunca quién es la persona.
 */
class VisitOrigin
{
    private const SESSION_KEY = 'visit_origin';

    /** @var array<string, string> Alias que la gente escribe en los enlaces → fuente normalizada. */
    private const SOURCES = [
        'instagram' => 'instagram', 'ig' => 'instagram',
        'facebook' => 'facebook', 'fb' => 'facebook', 'messenger' => 'facebook',
        'whatsapp' => 'whatsapp', 'wa' => 'whatsapp',
        'tiktok' => 'tiktok', 'tt' => 'tiktok',
        'google' => 'google', 'email' => 'email', 'newsletter' => 'email',
    ];

    /** @var array<string, string> */
    public const LABELS = [
        'instagram' => 'Instagram', 'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp',
        'tiktok' => 'TikTok', 'google' => 'Google', 'email' => 'Email', 'otro' => 'Otro sitio',
    ];

    /**
     * Guarda el origen en la sesión. Un enlace con UTM pisa el anterior; lo deducido solo completa.
     */
    public static function capture(Request $request): void
    {
        $tagged = $request->query('utm_source') ?? $request->query('from');

        if (is_string($tagged) && $tagged !== '') {
            $request->session()->put(self::SESSION_KEY, [
                'utm_source' => self::normalize($tagged),
                'utm_medium' => self::clean($request->query('utm_medium'), 60),
                'utm_campaign' => self::clean($request->query('utm_campaign'), 100),
            ]);

            return;
        }

        if ($request->session()->has(self::SESSION_KEY)) {
            return;
        }

        $guessed = self::fromUserAgent((string) $request->userAgent()) ?? self::fromReferer((string) $request->headers->get('referer'), $request->getHost());

        if ($guessed) {
            $request->session()->put(self::SESSION_KEY, ['utm_source' => $guessed, 'utm_medium' => null, 'utm_campaign' => null]);
        }
    }

    /**
     * Los campos para crear un lead desde la web.
     *
     * @return array{utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null}
     */
    public static function forLead(): array
    {
        return (array) session(self::SESSION_KEY, []);
    }

    public static function source(): ?string
    {
        return session(self::SESSION_KEY.'.utm_source');
    }

    public static function normalize(string $value): string
    {
        return self::SOURCES[Str::lower(trim($value))] ?? 'otro';
    }

    private static function fromUserAgent(string $agent): ?string
    {
        return match (true) {
            str_contains($agent, 'Instagram') => 'instagram',
            (bool) preg_match('/FBAN|FBAV|FB_IAB/', $agent) => 'facebook',
            (bool) preg_match('/musical_ly|BytedanceWebview|TikTok/i', $agent) => 'tiktok',
            default => null,
        };
    }

    private static function fromReferer(string $referer, string $ownHost): ?string
    {
        $host = Str::lower((string) parse_url($referer, PHP_URL_HOST));

        if ($host === '' || $host === Str::lower($ownHost)) {
            return null;
        }

        foreach (['instagram.com' => 'instagram', 'facebook.com' => 'facebook', 'tiktok.com' => 'tiktok', 'whatsapp.com' => 'whatsapp', 'google.' => 'google'] as $needle => $source) {
            if (str_contains($host, $needle)) {
                return $source;
            }
        }

        return 'otro';
    }

    private static function clean(mixed $value, int $max): ?string
    {
        return is_string($value) && trim($value) !== '' ? Str::limit(Str::slug($value, '_'), $max, '') : null;
    }
}
