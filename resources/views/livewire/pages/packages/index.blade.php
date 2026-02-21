<?php

use App\Models\TravelPackage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public string $search = '';

    public string $destination = '';

    public string $tag = '';

    public function clearFilters(): void
    {
        $this->search = '';
        $this->destination = '';
        $this->tag = '';
    }

    public function with(): array
    {
        return [
            'packages' => TravelPackage::query()
                ->where('is_active', true)
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->destination, fn($q) => $q->where('destination', $this->destination))
                ->when($this->tag, fn($q) => $q->whereJsonContains('tags', $this->tag))
                ->latest()
                ->get(),
            'featured' => TravelPackage::query()
                ->where('is_active', true)
                ->whereNotNull('cover_image')
                ->latest()
                ->take(5)
                ->get(),
            'destinations' => TravelPackage::where('is_active', true)
                ->distinct()
                ->pluck('destination')
                ->sort()
                ->values(),
            'allTags' => TravelPackage::where('is_active', true)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values(),
        ];
    }
} ?>

<div class="min-h-screen bg-gray-50" x-data="{ activeSlide: 0 }"
    x-init="setInterval(() => { activeSlide = (activeSlide + 1) % {{ $featured->count() ?: 1 }} }, 5000)">

    {{-- Hero Slider --}}
    <div class="relative h-[50vh] md:h-[60vh] overflow-hidden bg-gray-900">
        @foreach ($featured as $index => $feat)
            <a href="{{ route('packages.show', $feat->slug) }}" x-show="activeSlide === {{ $index }}"
                x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 scale-105"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-500"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 block">
                <img src="{{ str_starts_with($feat->cover_image, 'http') ? $feat->cover_image : asset('storage/' . $feat->cover_image) }}"
                    alt="{{ $feat->title }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 md:p-16 text-white">
                    <div class="max-w-7xl mx-auto">
                        @if ($feat->tags)
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach (array_slice($feat->tags, 0, 3) as $t)
                                    <span
                                        class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold uppercase tracking-wider">{{ $t }}</span>
                                @endforeach
                            </div>
                        @endif
                        <h2 class="text-3xl md:text-5xl font-extrabold leading-tight mb-2">{{ $feat->title }}</h2>
                        <p class="text-lg text-white/80">{{ $feat->destination }} · {{ $feat->nights }} noches · desde
                            <span class="text-amber-300 font-bold">{{ $feat->currency }}
                                {{ number_format($feat->price_from, 0, ',', '.') }}</span>
                        </p>
                    </div>
                </div>
            </a>
        @endforeach

        {{-- Slider Dots --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-20">
            @foreach ($featured as $index => $feat)
                <button @click="activeSlide = {{ $index }}"
                    :class="activeSlide === {{ $index }} ? 'w-8 bg-white' : 'w-2 bg-white/50'"
                    class="h-2 rounded-full transition-all duration-300"></button>
            @endforeach
        </div>

        {{-- Back to Home --}}
        <div class="absolute top-6 left-6 z-30">
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md rounded-full text-white text-sm font-medium hover:bg-white/30 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Inicio
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                {{-- Search --}}
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nombre del paquete..."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                </div>

                {{-- Destination --}}
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Destino</label>
                    <select wire:model.live="destination"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                        <option value="">Todos los destinos</option>
                        @foreach ($destinations as $dest)
                            <option value="{{ $dest }}">{{ $dest }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tag --}}
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Categoría</label>
                    <select wire:model.live="tag"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                        <option value="">Todas las categorías</option>
                        @foreach ($allTags as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear --}}
                <div>
                    <button wire:click="clearFilters"
                        class="w-full px-4 py-2.5 rounded-xl bg-gray-100 text-gray-600 text-sm font-semibold hover:bg-gray-200 transition-colors">
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Results --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center justify-between mb-8">
            <p class="text-sm text-gray-500">
                <span class="font-bold text-gray-900">{{ $packages->count() }}</span> propuestas encontradas
            </p>
        </div>

        @if ($packages->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($packages as $package)
                    <a href="{{ route('packages.show', $package->slug) }}"
                        class="group relative block aspect-[3/4] rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-1">
                        @if ($package->cover_image)
                            <img src="{{ str_starts_with($package->cover_image, 'http') ? $package->cover_image : asset('storage/' . $package->cover_image) }}"
                                alt="{{ $package->title }}"
                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        @else
                            <div class="absolute inset-0 bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500"></div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-black/60"></div>

                        {{-- Top Left: Title, Destination, Tags --}}
                        <div class="absolute top-0 left-0 p-5 text-white">
                            <h3 class="text-2xl font-extrabold leading-tight mb-2 drop-shadow-lg">{{ $package->title }}</h3>
                            <p class="text-sm text-white/80 flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $package->destination }} · {{ $package->nights }} noches
                            </p>
                            @if ($package->tags)
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach (array_slice($package->tags, 0, 3) as $tag)
                                        <span
                                            class="px-2.5 py-1 bg-white/20 backdrop-blur-sm rounded-full text-[10px] font-semibold uppercase tracking-wider">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Bottom Right: Price --}}
                        <div class="absolute bottom-0 right-0 p-5 text-white">
                            <span class="block text-sm font-bold italic text-white/80 mb-1">Desde</span>
                            <span class="block text-3xl font-extrabold text-amber-300 drop-shadow-lg">{{ $package->currency }}
                                {{ number_format($package->price_from, 0, ',', '.') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-20">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3 class="text-xl font-bold text-gray-400 mb-2">No encontramos propuestas</h3>
                <p class="text-gray-400 mb-6">Probá ajustando los filtros de búsqueda.</p>
                <button wire:click="clearFilters"
                    class="px-6 py-2.5 bg-amber-500 text-white rounded-xl font-semibold hover:bg-amber-600 transition-colors">
                    Ver todas
                </button>
            </div>
        @endif
    </div>

    {{-- Footer CTA --}}
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 py-12">
        <div class="max-w-4xl mx-auto px-4 text-center text-white">
            <h2 class="text-3xl font-extrabold mb-3">¿No encontrás lo que buscás?</h2>
            <p class="text-lg text-white/80 mb-6">Armamos tu viaje a medida. Contactanos y diseñamos la experiencia
                perfecta para vos.</p>
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 px-8 py-3 bg-white text-amber-600 font-bold rounded-xl hover:bg-gray-50 transition-all shadow-lg">
                Hablá con nosotros
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </a>
        </div>
    </div>
</div>