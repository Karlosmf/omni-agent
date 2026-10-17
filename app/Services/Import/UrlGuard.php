<?php

namespace App\Services\Import;

use InvalidArgumentException;

/**
 * Prevents server-side request forgery: only public http(s) hosts may be fetched.
 */
class UrlGuard
{
    /**
     * @throws InvalidArgumentException When the URL is not a public http(s) address.
     */
    public static function assertPublic(string $url): string
    {
        $parts = parse_url(trim($url));

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('La dirección debe empezar con http:// o https://.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('La dirección no puede incluir usuario ni contraseña.');
        }

        $host = trim($parts['host'], '[]');

        foreach (self::resolve($host) as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Esa dirección apunta a una red interna y no se puede importar.');
            }
        }

        return $url;
    }

    /**
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = array_filter([
            ...array_column(@dns_get_record($host, DNS_A) ?: [], 'ip'),
            ...array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        ]);

        if ($ips === []) {
            $fallback = gethostbyname($host);
            $ips = $fallback !== $host ? [$fallback] : [];
        }

        if ($ips === []) {
            throw new InvalidArgumentException('No se pudo resolver el sitio indicado.');
        }

        return array_values($ips);
    }
}
