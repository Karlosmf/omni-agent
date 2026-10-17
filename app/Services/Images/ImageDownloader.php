<?php

namespace App\Services\Images;

use App\Services\ImageService;
use App\Services\Import\SafeHttp;
use RuntimeException;

class ImageDownloader
{
    /**
     * Download a chosen candidate, convert it to WebP and return the stored path plus its credit.
     *
     * @param  array<string, mixed>  $candidate
     * @return array{path: string, credit: array<string, mixed>}
     */
    public function store(array $candidate, string $folder = 'ideas'): array
    {
        $response = SafeHttp::get($candidate['url'], 8_000_000);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo descargar la imagen.');
        }

        $path = ImageService::storeWebpFromString($response->body(), $folder);

        if (($candidate['provider'] ?? null) === 'Unsplash' && ! empty($candidate['download_ping'])) {
            try {
                app(ImageSearchService::class)->unsplash()->registerDownload($candidate['download_ping']);
            } catch (\Throwable) {
                // Registering the download is best effort.
            }
        }

        return [
            'path' => $path,
            'credit' => [
                'path' => $path,
                'author' => $candidate['author'] ?? null,
                'author_url' => $candidate['author_url'] ?? null,
                'provider' => $candidate['provider'] ?? 'Fuente',
                'license' => $candidate['license'] ?? null,
                'source_url' => $candidate['source_url'] ?? null,
            ],
        ];
    }
}
