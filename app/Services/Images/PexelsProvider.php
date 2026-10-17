<?php

namespace App\Services\Images;

use Illuminate\Support\Facades\Http;

class PexelsProvider implements ImageProvider
{
    public function __construct(private ?string $apiKey) {}

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function search(string $query, int $limit = 6): array
    {
        $response = Http::timeout(12)
            ->withHeaders(['Authorization' => $this->apiKey])
            ->get('https://api.pexels.com/v1/search', ['query' => $query, 'per_page' => $limit, 'orientation' => 'landscape']);

        return collect($response->json('photos') ?? [])->map(fn (array $photo) => [
            'id' => 'pexels:'.$photo['id'],
            'provider' => 'Pexels',
            'url' => $photo['src']['large2x'] ?? $photo['src']['large'],
            'thumb' => $photo['src']['medium'],
            'author' => $photo['photographer'] ?? 'Pexels',
            'author_url' => $photo['photographer_url'] ?? null,
            'license' => 'Pexels License',
            'source_url' => $photo['url'] ?? null,
            'query' => $query,
        ])->all();
    }
}
