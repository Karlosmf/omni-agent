<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    @php
        $import = $this->record;
        $package = $import->travelPackage;
        $status = $import->status;
        $lowConfidence = $import->lowConfidenceFields();
        $labels = ['price' => 'precio', 'nights' => 'noches', 'itinerary' => 'itinerario', 'included' => 'qué incluye', 'destination' => 'destino'];
    @endphp

    <div @if ($status->isProcessing()) wire:poll.3s="$refresh" @endif class="omni-stack">
        <x-filament::section>
            <div class="omni-row">
                <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                @if ($status->isProcessing())
                    <x-filament::loading-indicator style="height: 1.25rem; width: 1.25rem;" />
                    <span class="omni-muted">Leyendo la fuente y armando el borrador… puede tardar un minuto.</span>
                @endif
            </div>

            @if ($import->error)
                <p class="omni-text-sm omni-danger" style="margin: .75rem 0 0;">{{ $import->error }}</p>
            @endif
        </x-filament::section>

        @if ($package)
            @if ($import->warnings)
                <x-filament::section>
                    <x-slot name="heading">Avisos</x-slot>
                    <ul class="omni-bullets">
                        @foreach ($import->warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($lowConfidence)
                <x-filament::section>
                    <x-slot name="heading">Revisá estos datos con atención</x-slot>
                    <p class="omni-text-sm" style="margin: 0;">La IA no estaba segura de: <strong>{{ collect($lowConfidence)->keys()->map(fn ($k) => $labels[$k] ?? $k)->implode(', ') }}</strong>. Compará con la fuente.</p>
                </x-filament::section>
            @endif

            <div class="omni-two">
                <x-filament::section>
                    <x-slot name="heading">Borrador</x-slot>
                    <dl class="omni-dl" style="margin: 0;">
                        <div><dt>Título</dt><dd>{{ $package->title }}</dd></div>
                        <div><dt>Destino</dt><dd>{{ $package->destination }} · {{ $package->nights }} noches</dd></div>
                        <div><dt>Precio desde</dt><dd>{{ $package->currency }} {{ number_format($package->price_from, 2, ',', '.') }}</dd></div>
                        <div><dt>Resumen</dt><dd>{{ $package->summary ?: '—' }}</dd></div>
                        <div><dt>Itinerario</dt><dd>{{ count($package->itinerary ?? []) }} días</dd></div>
                    </dl>
                    <p class="omni-muted" style="margin: 1rem 0 0;">Para cambiar cualquier dato usá «Editar borrador». Nada se publica hasta que aprietes «Publicar».</p>
                </x-filament::section>

                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Texto de la fuente</x-slot>
                    <pre class="omni-source">{{ $import->extracted_text ?: ($import->source_text ?: 'PDF leído directamente por la IA.') }}</pre>
                </x-filament::section>
            </div>

            @if ($status === \App\Enums\PackageImportStatus::ReadyForReview)
                <x-filament::section>
                    <x-slot name="heading">Imágenes reales del destino</x-slot>
                    <x-slot name="description">Fotos con licencia libre (Unsplash, Pexels, Wikimedia). Marcá las que quieras y elegí la portada. Se guarda el crédito de cada autor.</x-slot>

                    @if (empty($import->image_candidates))
                        <p class="omni-text-sm omni-muted">No encontramos imágenes. Configurá las claves de Unsplash y Pexels en Configuración → Integraciones, o subí las fotos a mano desde «Editar borrador».</p>
                    @else
                        <div class="omni-images">
                            @foreach ($import->image_candidates as $candidate)
                                <label class="omni-image {{ in_array($candidate['id'], $selected) ? 'is-selected' : '' }}">
                                    <img src="{{ $candidate['thumb'] }}" alt="" loading="lazy" class="omni-thumb">
                                    <span class="omni-image-line">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $candidate['id'] }}">
                                        <span class="omni-ellipsis">{{ $candidate['provider'] }}@if ($candidate['author']) · {{ $candidate['author'] }}@endif</span>
                                    </span>
                                    @if (in_array($candidate['id'], $selected))
                                        <span class="omni-image-line" style="margin-top: .25rem;">
                                            <input type="radio" wire:model.live="cover" value="{{ $candidate['id'] }}"> Portada
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div style="margin-top: 1rem;">
                            <x-filament::button wire:click="applySelectedImages" icon="heroicon-o-photo">Usar las imágenes elegidas</x-filament::button>
                        </div>
                    @endif

                    @if ($package->image_credits)
                        <p class="omni-muted" style="margin: 1rem 0 0;">Créditos guardados: {{ collect($package->image_credits)->map(fn ($c) => trim(($c['author'] ?? '').' / '.($c['provider'] ?? ''), ' /'))->implode(' · ') }}</p>
                    @endif
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
