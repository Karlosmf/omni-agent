<?php

namespace App\Services\Images;

interface ImageProvider
{
    public function isConfigured(): bool;

    /**
     * Real, licensed photos for a search query.
     *
     * @return list<array<string, mixed>> Candidates: id, provider, url, thumb, author, author_url, license, source_url, query.
     */
    public function search(string $query, int $limit = 6): array;
}
