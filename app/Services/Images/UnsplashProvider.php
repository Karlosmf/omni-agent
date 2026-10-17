<?php

namespace App\Services\Images;

use Illuminate\Support\Facades\Http;

class UnsplashProvider implements ImageProvider
{
    public function __construct(private ?string $accessKey) {}

    public function isConfigured(): bool
    {
        return filled($this->accessKey);
    }

    public function search(string $query, int $limit = 6): array
    {
        $response = Http::timeout(12)
            ->withHeaders(['Authorization' => 'Client-ID '.$this->accessKey, 'Accept-Version' => 'v1'])
            ->get('https://api.unsplash.com/search/photos', ['query' => $query, 'per_page' => $limit, 'orientation' => 'landscape']);

        return collect($response->json('results') ?? [])->map(fn (array $photo) => [
            'id' => 'unsplash:'.$photo['id'],
            'provider' => 'Unsplash',
            'url' => $photo['urls']['regular'],
            'thumb' => $photo['urls']['small'],
            'author' => $photo['user']['name'] ?? 'Unsplash',
            'author_url' => $photo['user']['links']['html'] ?? null,
            'license' => 'Unsplash License',
            'source_url' => $photo['links']['html'] ?? null,
            'download_ping' => $photo['links']['download_location'] ?? null,
            'query' => $query,
        ])->all();
    }

    /**
     * Unsplash requires registering a download when a photo is actually used.
     */
    public function registerDownload(string $pingUrl): void
    {
        Http::timeout(8)->withHeaders(['Authorization' => 'Client-ID '.$this->accessKey])->get($pingUrl);
    }
}
