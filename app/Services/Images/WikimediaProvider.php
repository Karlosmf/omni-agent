<?php

namespace App\Services\Images;

use Illuminate\Support\Facades\Http;

/**
 * Wikimedia Commons: ideal for specific landmarks. The license varies per photo and is stored with it.
 */
class WikimediaProvider implements ImageProvider
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function search(string $query, int $limit = 6): array
    {
        $response = Http::timeout(12)
            ->withHeaders(['User-Agent' => 'OmniAgentImporter/1.0 (travel agency CMS)'])
            ->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => $query.' filetype:bitmap',
                'gsrnamespace' => 6,
                'gsrlimit' => $limit * 2,
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata|size|mime',
                'iiurlwidth' => 1280,
            ]);

        return collect($response->json('query.pages') ?? [])
            ->map(function (array $page) use ($query) {
                $info = $page['imageinfo'][0] ?? null;
                $meta = $info['extmetadata'] ?? [];
                $license = strip_tags($meta['LicenseShortName']['value'] ?? '');

                if (! $info
                    || ! in_array($info['mime'] ?? '', ['image/jpeg', 'image/png'], true)
                    || ($info['width'] ?? 0) < 1000
                    || ! preg_match('/^(CC|Public domain|PD)/i', $license)) {
                    return null;
                }

                return [
                    'id' => 'wikimedia:'.$page['pageid'],
                    'provider' => 'Wikimedia Commons',
                    'url' => $info['thumburl'] ?? $info['url'],
                    'thumb' => $info['thumburl'] ?? $info['url'],
                    'author' => trim(strip_tags($meta['Artist']['value'] ?? 'Autor desconocido')),
                    'author_url' => null,
                    'license' => $license,
                    'source_url' => $info['descriptionurl'] ?? null,
                    'query' => $query,
                ];
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }
}
