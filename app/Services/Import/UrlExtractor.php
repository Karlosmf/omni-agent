<?php

namespace App\Services\Import;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class UrlExtractor
{
    public const MAX_CHARS = 30000;

    /**
     * Download a page and return its readable text plus candidate image URLs.
     *
     * @return array{text: string, images: list<string>}
     */
    public function extract(string $url): array
    {
        $response = SafeHttp::get($url);

        if (! $response->successful()) {
            throw new RuntimeException("No se pudo abrir la página (HTTP {$response->status()}).");
        }

        return $this->parseHtml($response->body(), $url);
    }

    /**
     * @return array{text: string, images: list<string>}
     */
    public function parseHtml(string $html, string $baseUrl): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        $images = $this->imageCandidates($xpath, $baseUrl);

        foreach ($xpath->query('//script|//style|//noscript|//nav|//footer|//header|//aside|//form|//iframe|//svg|//button') as $node) {
            $node->parentNode?->removeChild($node);
        }

        $title = trim((string) $xpath->evaluate('string(//title)'));
        $body = $xpath->query('//main|//article|//body')->item(0);
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', (string) $body?->textContent);
        $text = trim(preg_replace('/\s*\n\s*/', "\n", $text));

        if (mb_strlen($text) < 80) {
            throw new RuntimeException('La página no tiene texto suficiente para importar.');
        }

        return [
            'text' => mb_substr(($title !== '' ? "{$title}\n\n" : '').$text, 0, self::MAX_CHARS),
            'images' => $images,
        ];
    }

    /**
     * @return list<string>
     */
    private function imageCandidates(DOMXPath $xpath, string $baseUrl): array
    {
        $urls = [];

        foreach ($xpath->query('//meta[@property="og:image" or @name="twitter:image"]/@content') as $attr) {
            $urls[] = $attr->nodeValue;
        }

        foreach ($xpath->query('//img/@src') as $attr) {
            $urls[] = $attr->nodeValue;
        }

        return collect($urls)
            ->filter()
            ->map(fn ($u) => SafeHttp::absolute($baseUrl, trim($u)))
            ->filter(fn ($u) => preg_match('#^https?://#i', $u) && ! preg_match('/\.(svg|gif|ico)(\?|$)|logo|icon|sprite|avatar|pixel|banner-ads/i', $u))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
