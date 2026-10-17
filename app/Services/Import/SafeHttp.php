<?php

namespace App\Services\Import;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Fetches user-supplied URLs safely: public hosts only (checked on every redirect), a size cap and a timeout.
 */
class SafeHttp
{
    public const MAX_BYTES = 5_000_000;

    private const MAX_REDIRECTS = 3;

    public static function get(string $url, int $maxBytes = self::MAX_BYTES): Response
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            UrlGuard::assertPublic($url);

            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OmniAgentImporter/1.0)', 'Accept-Language' => 'es,en;q=0.8'])
                ->withOptions([
                    'allow_redirects' => false,
                    'on_headers' => function (ResponseInterface $r) use ($maxBytes) {
                        if ((int) $r->getHeaderLine('Content-Length') > $maxBytes) {
                            throw new RuntimeException('El archivo es demasiado grande.');
                        }
                    },
                ])
                ->get($url);

            if ($response->redirect() && $response->header('Location')) {
                $url = self::absolute($url, $response->header('Location'));

                continue;
            }

            if (strlen($response->body()) > $maxBytes) {
                throw new RuntimeException('El archivo es demasiado grande.');
            }

            return $response;
        }

        throw new RuntimeException('Demasiadas redirecciones.');
    }

    public static function absolute(string $base, string $relative): string
    {
        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        $parts = parse_url($base);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($relative, '//')) {
            return $parts['scheme'].':'.$relative;
        }

        if (str_starts_with($relative, '/')) {
            return $origin.$relative;
        }

        $dir = rtrim(dirname($parts['path'] ?? '/'), '/');

        return $origin.$dir.'/'.$relative;
    }
}
