<?php

namespace App\Services\Images;

use App\Models\AgencySetting;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds real, licensed photos of a destination. Never AI-generated images, never Google Images.
 */
class ImageSearchService
{
    /**
     * @param  list<ImageProvider>|null  $providers
     */
    public function __construct(private ?array $providers = null) {}

    /**
     * @param  list<string>  $queries
     * @return list<array<string, mixed>>
     */
    public function search(array $queries, int $perQuery = 6): array
    {
        $candidates = [];

        foreach ($queries as $query) {
            foreach ($this->providers() as $provider) {
                if (! $provider->isConfigured()) {
                    continue;
                }

                try {
                    array_push($candidates, ...$provider->search($query, $perQuery));
                } catch (Throwable $e) {
                    Log::warning(class_basename($provider).' image search failed: '.$e->getMessage());
                }
            }
        }

        return collect($candidates)->unique('id')->values()->all();
    }

    /**
     * @return list<ImageProvider>
     */
    public function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        /** @var AgencySetting|null $settings */
        $settings = get_agency_settings();

        return [
            new UnsplashProvider($settings?->unsplash_access_key),
            new PexelsProvider($settings?->pexels_api_key),
            new WikimediaProvider,
        ];
    }

    public function unsplash(): UnsplashProvider
    {
        return new UnsplashProvider(get_agency_settings()?->unsplash_access_key);
    }
}
