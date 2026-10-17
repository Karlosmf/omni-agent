<x-filament-panels::page>
    @php
        $import = $this->record;
        $package = $import->travelPackage;
        $status = $import->status;
        $lowConfidence = $import->lowConfidenceFields();
        $labels = ['price' => 'precio', 'nights' => 'noches', 'itinerary' => 'itinerario', 'included' => 'qué incluye', 'destination' => 'destino'];
    @endphp

    <div @if ($status->isProcessing()) wire:poll.3s="$refresh" @endif class="space-y-6">
        <x-filament::section>
            <div class="flex items-center gap-3">
                <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                @if ($status->isProcessing())
                    <x-filament::loading-indicator class="h-5 w-5" />
                    <span class="text-sm text-gray-500">Leyendo la fuente y armando el borrador… puede tardar un minuto.</span>
                @endif
            </div>

            @if ($import->error)
                <p class="mt-3 text-sm text-danger-600">{{ $import->error }}</p>
            @endif
        </x-filament::section>

        @if ($package)
            @if ($import->warnings)
                <x-filament::section>
                    <x-slot name="heading">Avisos</x-slot>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($import->warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($lowConfidence)
                <x-filament::section>
                    <x-slot name="heading">Revisá estos datos con atención</x-slot>
                    <p class="text-sm">La IA no estaba segura de: <strong>{{ collect($lowConfidence)->keys()->map(fn ($k) => $labels[$k] ?? $k)->implode(', ') }}</strong>. Compará con la fuente.</p>
                </x-filament::section>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Borrador</x-slot>
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-semibold">Título</dt><dd>{{ $package->title }}</dd></div>
                        <div><dt class="font-semibold">Destino</dt><dd>{{ $package->destination }} · {{ $package->nights }} noches</dd></div>
                        <div><dt class="font-semibold">Precio desde</dt><dd>{{ $package->currency }} {{ number_format($package->price_from, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Resumen</dt><dd>{{ $package->summary ?: '—' }}</dd></div>
                        <div><dt class="font-semibold">Itinerario</dt><dd>{{ count($package->itinerary ?? []) }} días</dd></div>
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">Para cambiar cualquier dato usá «Editar borrador». Nada se publica hasta que aprietes «Publicar».</p>
                </x-filament::section>

                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Texto de la fuente</x-slot>
                    <pre class="max-h-96 overflow-auto whitespace-pre-wrap text-xs">{{ $import->extracted_text ?: ($import->source_text ?: 'PDF leído directamente por la IA.') }}</pre>
                </x-filament::section>
            </div>

            @if ($status === \App\Enums\PackageImportStatus::ReadyForReview)
                <x-filament::section>
                    <x-slot name="heading">Imágenes reales del destino</x-slot>
                    <x-slot name="description">Fotos con licencia libre (Unsplash, Pexels, Wikimedia). Marcá las que quieras y elegí la portada. Se guarda el crédito de cada autor.</x-slot>

                    @if (empty($import->image_candidates))
                        <p class="text-sm text-gray-500">No encontramos imágenes. Configurá las claves de Unsplash y Pexels en Configuración → Integraciones, o subí las fotos a mano desde «Editar borrador».</p>
                    @else
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            @foreach ($import->image_candidates as $candidate)
                                <label class="block cursor-pointer rounded-lg border p-2 text-xs {{ in_array($candidate['id'], $selected) ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200' }}">
                                    <img src="{{ $candidate['thumb'] }}" alt="" loading="lazy" class="mb-2 h-28 w-full rounded object-cover">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $candidate['id'] }}">
                                        <span class="truncate">{{ $candidate['provider'] }}@if ($candidate['author']) · {{ $candidate['author'] }}@endif</span>
                                    </span>
                                    @if (in_array($candidate['id'], $selected))
                                        <span class="mt-1 flex items-center gap-2">
                                            <input type="radio" wire:model.live="cover" value="{{ $candidate['id'] }}"> Portada
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <x-filament::button wire:click="applySelectedImages" icon="heroicon-o-photo">Usar las imágenes elegidas</x-filament::button>
                        </div>
                    @endif

                    @if ($package->image_credits)
                        <p class="mt-4 text-xs text-gray-500">Créditos guardados: {{ collect($package->image_credits)->map(fn ($c) => trim(($c['author'] ?? '').' / '.($c['provider'] ?? ''), ' /'))->implode(' · ') }}</p>
                    @endif
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
