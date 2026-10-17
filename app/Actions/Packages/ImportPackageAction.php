<?php

namespace App\Actions\Packages;

use App\Enums\PriceBasis;
use App\Models\PackageImport;
use App\Models\TravelPackage;
use Illuminate\Support\Str;

/**
 * Creates the unpublished draft TravelPackage from a structured payload.
 */
class ImportPackageAction
{
    /**
     * @param  array<string, mixed>  $payload  Normalized payload from TravelPackageExtractor.
     */
    public function createDraft(PackageImport $import, array $payload): TravelPackage
    {
        $warnings = [];

        $duplicate = TravelPackage::where('destination', $payload['destination'])
            ->when($payload['nights'], fn ($q, $nights) => $q->where('nights', $nights))
            ->first();

        if ($duplicate) {
            $warnings[] = "Ya existe una idea parecida: «{$duplicate->title}» (mismo destino y noches).";
        }

        if ($payload['price_from'] === null) {
            $warnings[] = 'La fuente no indicaba un precio: completalo antes de publicar.';
        }

        $package = TravelPackage::create([
            'title' => $payload['title'],
            'slug' => $this->uniqueSlug($payload['title']),
            'destination' => $payload['destination'],
            'nights' => $payload['nights'] ?? 0,
            'summary' => $payload['summary'] ? Str::limit($payload['summary'], 500, '') : null,
            'description' => $payload['description'],
            'itinerary' => $payload['itinerary'] ?: null,
            'included' => implode("\n", $payload['included']) ?: null,
            'excluded' => implode("\n", $payload['excluded']) ?: null,
            'tags' => $payload['tags'] ?: null,
            'price_from' => $payload['price_from'] ?? 0,
            'currency' => $payload['currency'] ?? 'USD',
            'price_basis' => PriceBasis::tryFrom((string) $payload['price_basis'])?->value ?? PriceBasis::PorPersona->value,
            'seasons' => $this->seasons($payload['seasons']) ?: null,
            'is_active' => false,
            'source_import_id' => $import->id,
        ]);

        $import->update([
            'ai_payload' => $payload,
            'travel_package_id' => $package->id,
            'warnings' => $warnings ?: null,
        ]);

        return $package;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'idea-de-viaje';
        $slug = $base;

        for ($i = 2; TravelPackage::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Keep only seasons with valid ISO dates and a price.
     *
     * @param  array<int, mixed>  $seasons
     * @return list<array<string, mixed>>
     */
    private function seasons(array $seasons): array
    {
        return collect($seasons)
            ->filter(fn ($s) => is_array($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($s['from'] ?? '')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($s['to'] ?? '')) && is_numeric($s['price_from'] ?? null))
            ->map(fn ($s) => ['name' => (string) ($s['name'] ?? ''), 'from' => $s['from'], 'to' => $s['to'], 'price_from' => (float) $s['price_from']])
            ->values()
            ->all();
    }
}
