<?php

use App\Models\TravelPackage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    public ?TravelPackage $package = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $success = false;

    public function mount(string $slug): void
    {
        $this->package = TravelPackage::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function submitLead(\App\Services\AiConciergeService $aiService): void
    {
        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $fullMessage = "Consulta por paquete: {$this->package->title} ({$this->package->nights} noches, {$this->package->destination}).\n".
                       'Mensaje del cliente: '.($this->message ?: 'Sin comentarios adicionales.');

        $captureLeadAction = app(\App\Actions\Leads\CaptureLeadAction::class);
        $lead = $captureLeadAction->execute([
            'customer_name' => $this->name,
            'customer_phone' => $this->phone ?: 'No provisto',
            'customer_email' => $this->email,
            'source' => 'web_form',
            'raw_message' => $fullMessage,
            'ai_data' => [
                'email' => $this->email,
                'package_id' => $this->package->id,
            ],
            'travel_package_id' => $this->package->id,
        ]);

        try {
            $extraction = $aiService->extractLeadData("El usuario {$this->name} consultó por el paquete {$this->package->title}: {$this->message}");

            $currentAiData = $lead->ai_data ?? [];
            $newAiData = array_merge($currentAiData, [
                'destino' => $extraction['destino'] ?? $this->package->destination,
                'pasajeros' => $extraction['pasajeros'] ?? 1,
            ]);

            $lead->update([
                'ai_data' => $newAiData,
                'ai_summary' => $extraction['resumen'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error processing package lead with AI: '.$e->getMessage());
        }

        $this->success = true;
    }

    public function with(): array
    {
        $images = [];
        if ($this->package->cover_image) {
            $images[] = $this->package->cover_image;
        }
        if (is_array($this->package->gallery)) {
            $images = array_merge($images, $this->package->gallery);
        }
        
        return [
            'galleryImages' => $images,
            'itinerary' => $this->package->itinerary ?? [],
        ];
    }
} ?>

    <div class="min-h-screen bg-gray-50">

        {{-- Hero / Gallery --}}
        <div class="relative h-[60vh] md:h-[70vh] overflow-hidden bg-gray-900"
            x-data="{ activeSlide: 0 }"
            x-init="if ({{ count($galleryImages) }} > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % {{ count($galleryImages) }} }, 5000) }"
            wire:ignore>
            @if (count($galleryImages) > 0)
                @foreach ($galleryImages as $index => $image)
                    <div x-show="activeSlide === {{ $index }}" x-transition:enter="transition ease-out duration-700"
                        x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" class="absolute inset-0">
                        <img src="{{ str_starts_with($image, 'http') ? $image : asset('uploads/' . $image) }}" alt="{{ $package->title }}"
                            class="w-full h-full object-cover">
                    </div>
                @endforeach

                {{-- Slider Controls --}}
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                    @foreach ($galleryImages as $index => $image)
                        <button @click="activeSlide = {{ $index }}"
                            :class="activeSlide === {{ $index }} ? 'w-8 bg-white' : 'w-2 bg-white/50'"
                            class="h-2 rounded-full transition-all duration-300"></button>
                    @endforeach
                </div>
            @elseif ($package->cover_image)
                <img src="{{ str_starts_with($package->cover_image, 'http') ? $package->cover_image : asset('uploads/' . $package->cover_image) }}"
                    alt="{{ $package->title }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500"></div>
            @endif

            {{-- Dark overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent z-10"></div>

            {{-- Title Over Image --}}
            <div class="absolute bottom-0 left-0 right-0 z-20 p-8 md:p-16">
                <div class="max-w-7xl mx-auto">
                    <a href="{{ route('packages.index') }}"
                        class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm font-medium mb-4 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a Ideas de Viaje
                    </a>
                    <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight mb-2">
                        {{ $package->title }}
                    </h1>
                    <p class="text-lg text-white/80 flex items-center gap-3 flex-wrap">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $package->destination }}
                        </span>
                        <span class="text-white/40">·</span>
                        <span>{{ $package->nights }} noches</span>
                    </p>
                </div>
            </div>

            {{-- Share Button --}}
            <div class="absolute top-6 right-6 z-30">
                <button
                    x-on:click="navigator.share ? navigator.share({ title: '{{ addslashes($package->title) }}', url: window.location.href }) : null"
                    class="p-3 bg-white/20 backdrop-blur-md rounded-full text-white hover:bg-white/30 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                {{-- Main Content (col-span-2) --}}
                <div class="md:col-span-2 space-y-12">
                    {{-- Description --}}
                    @if ($package->description)
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Descripción</h2>
                            <div class="prose prose-lg text-gray-600 max-w-none">
                                {!! nl2br(e($package->description)) !!}
                            </div>
                        </div>
                    @endif

                    {{-- Itinerary --}}
                    @if (count($itinerary) > 0)
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Itinerario</h2>
                            <div class="space-y-0">
                                @foreach ($itinerary as $index => $day)
                                    <div
                                        class="relative pl-8 pb-8 {{ ! $loop->last ? 'border-l-2 border-amber-300' : '' }} ml-3">
                                        {{-- Timeline Dot --}}
                                        <div
                                            class="absolute -left-3 top-0 w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center">
                                            <span class="text-white text-[10px] font-bold">{{ $index + 1 }}</span>
                                        </div>

                                        <div
                                            class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                            <h3 class="text-lg font-bold text-gray-900">
                                                {{ $day['title'] ?? '' }}</h3>
                                            @if (! empty($day['description']))
                                                <p class="text-gray-600 mt-2 text-sm leading-relaxed">
                                                    {{ $day['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar (col-span-1) --}}
                <div class="md:col-span-1">
                    <div class="sticky top-8 space-y-6">
                        {{-- Pricing Card --}}
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-6 text-white">
                                <p class="text-sm font-medium text-white/80">Precio desde</p>
                                <p class="text-4xl font-extrabold mt-1">{{ $package->currency }}
                                    {{ number_format($package->price_from, 0, ',', '.') }}
                                </p>
                                <p class="text-sm text-white/70 mt-1">por persona</p>
                            </div>

                            <div class="p-6 space-y-4">
                                <div class="flex items-center gap-3 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span><strong>Destino:</strong> {{ $package->destination }}</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <span><strong>Noches:</strong> {{ $package->nights }}</span>
                                </div>

                                {{-- Tags --}}
                                @if ($package->tags)
                                    <div class="flex flex-wrap gap-1.5 pt-2">
                                        @foreach ($package->tags as $tag)
                                            <span
                                                class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-semibold">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="px-6 pb-6" x-data="{ showForm: false }">
                                @if($success)
                                    <div class="bg-green-50 text-green-800 p-4 rounded-xl text-center border border-green-200">
                                        <h4 class="font-bold text-lg mb-1 flex items-center justify-center gap-2">
                                            <span class="text-green-500">✓</span> ¡Consulta enviada!
                                        </h4>
                                        <p class="text-sm">Nos pondremos en contacto a la brevedad.</p>
                                    </div>
                                @else
                                    <button x-show="!showForm" @click="showForm = true" type="button"
                                        class="block w-full py-3 px-6 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-center font-bold rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg hover:shadow-xl active:scale-[0.98]">
                                        ¡Quiero este viaje! ✈️
                                    </button>

                                    <form x-show="showForm" style="display: none;" wire:submit="submitLead" class="space-y-4">
                                        <div>
                                            <input wire:model="name" type="text" placeholder="Nombre completo *" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <input wire:model="email" type="email" placeholder="Email *" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <input wire:model="phone" type="tel" placeholder="Teléfono" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <textarea wire:model="message" rows="2" placeholder="Comentarios o dudas adicionales..." class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"></textarea>
                                            @error('message') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" @click="showForm = false" class="px-4 py-2.5 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-bold transition-colors w-1/3">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="bg-amber-500 text-white font-bold py-2.5 rounded-lg shadow-md hover:bg-amber-600 transition-colors w-2/3 flex justify-center items-center group">
                                                <span wire:loading.remove wire:target="submitLead">Enviar ahora</span>
                                                <span wire:loading wire:target="submitLead" class="flex items-center gap-2">
                                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Included / Excluded --}}
                        @if ($package->included)
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-green-500">✅</span> Incluye
                                </h3>
                                <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                                    {{ $package->included }}
                                </div>
                            </div>
                        @endif

                        @if ($package->excluded)
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-red-500">❌</span> No incluye
                                </h3>
                                <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                                    {{ $package->excluded }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>