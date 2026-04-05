<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ get_agency_favicon() }}">
    <title>{{ $agencySettings?->company_name ?? config('app.name', 'Omni-Agent') }}</title>
    @if($agencySettings?->meta_description)
        <meta name="description" content="{{ $agencySettings->meta_description }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700" rel="stylesheet" />

    <!-- Tailwind / Vite -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    @if($agencySettings)
        <style>
            :root {
                --p: {{ hex_to_oklch($agencySettings->fe_primary_color) }};
                --s: {{ hex_to_oklch($agencySettings->fe_secondary_color) }};
                --a: {{ hex_to_oklch($agencySettings->fe_accent_color) }};
                --b1: {{ hex_to_oklch($agencySettings->fe_base_100_color) }};
                --bc: {{ hex_to_oklch($agencySettings->fe_base_content_color) }};
                --color-primary: {{ $agencySettings->fe_primary_color }};
                --color-secondary: {{ $agencySettings->fe_secondary_color }};
                --color-accent: {{ $agencySettings->fe_accent_color }};
                --color-base-100: {{ $agencySettings->fe_base_100_color }};
                --color-base-content: {{ $agencySettings->fe_base_content_color }};
            }
            
            body {
                background-color: var(--color-base-100) !important;
                color: var(--color-base-content) !important;
            }

            .from-amber-500 { --tw-gradient-from: var(--color-primary) !important; }
            .to-orange-600 { --tw-gradient-to: var(--color-secondary) !important; }
            .text-amber-600 { color: var(--color-primary) !important; }
            .bg-amber-100 { background-color: color-mix(in srgb, var(--color-primary) 15%, transparent) !important; }
            .text-amber-800, .text-amber-700 { color: var(--color-primary) !important; }
        </style>
    @endif

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f2f5;
            background-image: url("{{ asset('images/landing/bg-pattern.png') }}");
            background-repeat: repeat;
            background-size: 400px;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @livewireStyles
    @if($agencySettings?->header_scripts)
        {!! $agencySettings->header_scripts !!}
    @endif
</head>

<body class="antialiased min-h-screen relative flex flex-col"
    x-data="{ showConsultasModal: false, showWhatsAppDropdown: false, showMobileMenu: false }">

    <!-- Navbar / Header (Glassmorphism) -->
    <nav
        class="fixed top-0 w-full z-50 transition-all duration-300 bg-white/70 backdrop-blur-md border-b border-white/20 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    @if($agencySettings?->logo_path)
                        <img class="h-12 w-auto" src="{{ asset('storage/' . $agencySettings->logo_path) }}" alt="{{ $agencySettings->company_name }}">
                    @else
                        <img class="h-12 w-auto" src="{{ get_agency_logo() }}" alt="{{ config('app.name') }}">
                    @endif
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <button @click="showConsultasModal = true"
                        class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Consultas</button>

                    <!-- WhatsApp Dropdown -->
                    <div class="relative" @click.away="showWhatsAppDropdown = false">
                        <button @click="showWhatsAppDropdown = !showWhatsAppDropdown"
                            class="text-gray-600 hover:text-green-600 font-medium transition-colors flex items-center gap-1 group">
                            <span>WhatsApp</span>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                :class="showWhatsAppDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="showWhatsAppDropdown" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-xl border border-gray-100 py-2 z-50 overflow-hidden"
                            style="display: none;">
                            @php
                                $whatsappLinks = collect($agencySettings?->social_links ?? [])
                                    ->filter(fn($link) => 
                                        str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') || 
                                        str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
                                    );
                            @endphp

                            @if($whatsappLinks->isNotEmpty())
                                @foreach($whatsappLinks as $link)
                                    @php
                                        $platformName = $link['platform'] ?? 'WhatsApp';
                                        $displayName = str_ireplace('WhatsApp', '', $platformName);
                                        $displayName = trim($displayName) ?: 'WhatsApp';
                                        $initial = strtoupper(substr($displayName, 0, 1));
                                    @endphp
                                    <a href="{{ format_social_link($link['url']) }}" target="_blank"
                                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                                        <div
                                            class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold font-mono">
                                            {{ $initial }}</div>
                                        <span class="font-medium">{{ $displayName }}</span>
                                    </a>
                                @endforeach
                            @else
                                <a href="https://wa.link/16om0v" target="_blank"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold font-mono">
                                        B</div>
                                    <span class="font-medium">Belén</span>
                                </a>
                                <a href="https://wa.link/28mpwn" target="_blank"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold font-mono">
                                        N</div>
                                    <span class="font-medium">Nela</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @php
                        $instagramLink = collect($agencySettings?->social_links ?? [])->first(fn($l) => str_contains(strtolower($l['platform'] ?? ''), 'instagram'));
                    @endphp
                    @if($instagramLink)
                        <a href="{{ format_social_link($instagramLink['url']) }}" target="_blank"
                            class="text-gray-600 hover:text-pink-600 transition-colors">
                            <i class="ph-bold ph-instagram-logo text-2xl"></i>
                        </a>
                    @endif
                    @auth
                        <a href="{{ url('/admin') }}"
                            class="px-5 py-2.5 rounded-full bg-gray-900 text-white font-medium hover:bg-gray-800 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="ph-bold ph-gauge text-xl"></i>
                            <span>Panel</span>
                        </a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}"
                            class="px-5 py-2.5 rounded-full bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="ph-bold ph-user-circle text-xl"></i>
                            <span>Ingresar</span>
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <button @click="showMobileMenu = !showMobileMenu" type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                        <i class="ph-bold text-2xl" :class="showMobileMenu ? 'ph-x' : 'ph-list'"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="showMobileMenu" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden bg-white border-t border-gray-100 shadow-lg" style="display: none;">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <button @click="showConsultasModal = true; showMobileMenu = false"
                    class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-amber-600 hover:bg-amber-50">
                    Consultas
                </button>

                <!-- WhatsApp Mobile Group -->
                <div class="px-3 py-2">
                    <span
                        class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">WhatsApp</span>
                    <div class="flex flex-col gap-2 pl-2">
                        @php
                            $whatsappLinks = collect($agencySettings?->social_links ?? [])
                                ->filter(fn($link) => 
                                    str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') || 
                                    str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
                                );
                        @endphp

                        @if($whatsappLinks->isNotEmpty())
                            @foreach($whatsappLinks as $link)
                                @php
                                    $platformName = $link['platform'] ?? 'WhatsApp';
                                    $displayName = str_ireplace('WhatsApp', '', $platformName);
                                    $displayName = trim($displayName) ?: 'WhatsApp';
                                    $initial = strtoupper(substr($displayName, 0, 1));
                                @endphp
                                <a href="{{ format_social_link($link['url']) }}" target="_blank"
                                    class="flex items-center gap-2 text-gray-700 hover:text-green-600">
                                    <div
                                        class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-[10px] font-bold">
                                        {{ $initial }}</div>
                                    <span>{{ $displayName }}</span>
                                </a>
                            @endforeach
                        @else
                            <a href="https://wa.link/16om0v" target="_blank"
                                class="flex items-center gap-2 text-gray-700 hover:text-green-600">
                                <div
                                    class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-[10px] font-bold">
                                    B</div>
                                <span>Belén</span>
                            </a>
                            <a href="https://wa.link/28mpwn" target="_blank"
                                class="flex items-center gap-2 text-gray-700 hover:text-green-600">
                                <div
                                    class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-[10px] font-bold">
                                    N</div>
                                <span>Nela</span>
                            </a>
                        @endif
                    </div>
                </div>

                @if($instagramLink)
                    <a href="{{ format_social_link($instagramLink['url']) }}" target="_blank"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-pink-600 hover:bg-pink-50">
                        Instagram
                    </a>
                @endif

                @auth
                    <a href="{{ url('/admin') }}"
                        class="block w-full text-center mt-4 px-5 py-3 rounded-xl bg-gray-900 text-white font-medium hover:bg-gray-800">
                        Ir al Panel
                    </a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}"
                        class="block w-full text-center mt-4 px-5 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium">
                        Ingresar
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col pt-20">

        @php
            $sliders = \App\Models\HeroSlider::where('is_active', true)->orderBy('sort_order')->get();
        @endphp

        @if($sliders->isNotEmpty())
            <!-- Hero Slider -->
            <div x-data="{ 
                activeSlide: 0, 
                slidesCount: {{ $sliders->count() }},
                autoplay() {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.slidesCount;
                    }, 6000);
                }
            }" x-init="autoplay()" class="relative overflow-hidden">

                @foreach($sliders as $index => $slide)
                    <div x-show="activeSlide === {{ $index }}" 
                         x-transition:enter="transition ease-out duration-1000"
                         x-transition:enter-start="opacity-0 transform translate-x-full"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         x-transition:leave="transition ease-in duration-1000"
                         x-transition:leave-start="opacity-100 transform translate-x-0"
                         x-transition:leave-end="opacity-0 transform -translate-x-full"
                         class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 flex flex-col lg:flex-row items-center gap-12 lg:gap-20 min-h-[600px]">

                        <!-- Text Content -->
                        <div class="w-full lg:w-1/2 space-y-8 text-center lg:text-left z-10">
                            @if($slide->subtitle)
                                <div
                                    class="inline-block px-4 py-1.5 rounded-full bg-amber-100/80 text-amber-800 font-medium text-sm backdrop-blur-sm border border-amber-200">
                                    {{ $slide->subtitle }}
                                </div>
                            @endif
                            <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight">
                                {!! str_replace($agencySettings?->company_name ?? 'Omni-Agent', '<span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">' . ($agencySettings?->company_name ?? 'Omni-Agent') . '</span>', e($slide->title)) !!}
                            </h1>
                            @if($slide->description)
                                <p
                                    class="text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 bg-white/60 p-4 rounded-xl backdrop-blur-sm border border-white/40 shadow-sm">
                                    {{ $slide->description }}
                                </p>
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                                @if($slide->cta_button_text && $slide->cta_button_url)
                                    <a href="{{ $slide->cta_button_url }}" target="_blank"
                                        class="px-8 py-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl flex items-center justify-center gap-2">
                                        <span>{{ $slide->cta_button_text }}</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                        </svg>
                                    </a>
                                @endif
                                @if($slide->sec_button_text && $slide->sec_button_url)
                                    <a href="{{ $slide->sec_button_url }}" target="_blank"
                                        class="px-8 py-4 rounded-xl bg-white text-gray-900 font-semibold hover:bg-gray-50 border border-gray-200 transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                                        <span>{{ $slide->sec_button_text }}</span>
                                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="w-full lg:w-1/2 relative flex flex-col items-center">
                            <div class="relative w-full max-w-md transform transition-all hover:scale-[1.02] duration-500">
                                <div
                                    class="absolute -top-10 -right-10 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob">
                                </div>
                                <div
                                    class="absolute -bottom-10 -left-10 w-64 h-64 bg-amber-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000">
                                </div>

                                <div class="absolute inset-0 bg-gray-900 rounded-3xl rotate-3 opacity-20 blur-lg"></div>
                                <div class="relative rounded-3xl shadow-2xl overflow-hidden border-4 border-white aspect-[4/5] bg-gray-100">
                                    <img src="{{ asset('storage/' . $slide->image_path) }}" class="w-full h-full object-cover" alt="{{ $slide->title }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Slider Indicators -->
                @if($sliders->count() > 1)
                    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-2 z-20">
                        @foreach($sliders as $index => $slide)
                            <button @click="activeSlide = {{ $index }}" 
                                    class="w-3 h-3 rounded-full transition-all duration-300"
                                    :class="activeSlide === {{ $index }} ? 'bg-amber-500 w-8' : 'bg-gray-300'"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <!-- Hero Section (Fallback) -->
            <div
                class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 flex flex-col lg:flex-row items-center gap-12 lg:gap-20">

                <!-- Text Content -->
                <div class="w-full lg:w-1/2 space-y-8 text-center lg:text-left z-10">
                    <div
                        class="inline-block px-4 py-1.5 rounded-full bg-amber-100/80 text-amber-800 font-medium text-sm backdrop-blur-sm border border-amber-200">
                        ✈️ Tu compañera de viajes
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight">
                        Descubre el mundo con <span
                            class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">{{ $agencySettings->company_name ?? 'Omni-Agent' }}</span>
                    </h1>
                    <p
                        class="text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 bg-white/60 p-4 rounded-xl backdrop-blur-sm border border-white/40 shadow-sm">
                        Planificamos tu próxima aventura con dedicación y experiencia. Desde escapadas locales hasta
                        destinos exóticos, hacemos realidad el viaje de tus sueños.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        @if($agencySettings?->hero_cta_url)
                            <a href="{{ $agencySettings->hero_cta_url }}" target="_blank"
                                class="px-8 py-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl flex items-center justify-center gap-2">
                                <span>Planear mi viaje</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </a>
                        @endif
                        @if($agencySettings?->google_maps_url)
                            <a href="{{ $agencySettings->google_maps_url }}" target="_blank"
                                class="px-8 py-4 rounded-xl bg-white text-gray-900 font-semibold hover:bg-gray-50 border border-gray-200 transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                                <span>Ver ubicación</span>
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Image & Lead Form -->
                <div class="w-full lg:w-1/2 relative flex flex-col items-center">

                    <!-- Hero Image Stack Card -->
                    <div class="relative w-full max-w-md transform transition-all hover:scale-[1.02] duration-500">
                        <!-- Decorative Elements aligned to vertices -->
                        <div
                            class="absolute -top-10 -right-10 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob">
                        </div>
                        <div
                            class="absolute -bottom-10 -left-10 w-64 h-64 bg-amber-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000">
                        </div>

                        <div class="absolute inset-0 bg-gray-900 rounded-3xl rotate-3 opacity-20 blur-lg"></div>
                        <div class="relative rounded-3xl shadow-2xl overflow-hidden border-4 border-white aspect-[4/5]">
                            <x-photo-stack />
                        </div>
                    </div>
                    <!-- Spacer for the form overlap -->
                    <div class="h-48 hidden lg:block"></div>
                </div>

            </div>
        @endif

        <!-- Travel Packages Section -->
        <div id="travel-packages" class="px-8 sm:px-12 lg:px-16 py-12">
            <div class="text-center mb-16">
                <span
                    class="inline-block px-4 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold tracking-wide uppercase mb-4">✈️
                    Ideas de Viaje</span>
                <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Nuestras propuestas de viaje</h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">Descubrí destinos increíbles con paquetes diseñados
                    especialmente para vos.</p>
            </div>

            @php $packages = \App\Models\TravelPackage::where('is_active', true)->latest()->get(); @endphp

            <div x-data="{
                scrollContainer: null,
                canScrollLeft: false,
                canScrollRight: true,
                autoplayTimer: null,
                pauseTimeout: null,
                init() {
                    this.scrollContainer = this.$refs.slider;
                    this.checkScroll();
                    this.scrollContainer.addEventListener('scroll', () => this.checkScroll());
                    this.startAutoplay();
                },
                checkScroll() {
                    this.canScrollLeft = this.scrollContainer.scrollLeft > 10;
                    this.canScrollRight = this.scrollContainer.scrollLeft < (this.scrollContainer.scrollWidth - this.scrollContainer.clientWidth - 10);
                },
                scrollByCard(direction) {
                    const cardWidth = this.scrollContainer.querySelector('a').offsetWidth + 24;
                    this.scrollContainer.scrollBy({ left: direction * cardWidth, behavior: 'smooth' });
                },
                userScroll(direction) {
                    this.stopAutoplay();
                    this.scrollByCard(direction);
                    clearTimeout(this.pauseTimeout);
                    this.pauseTimeout = setTimeout(() => this.startAutoplay(), 5000);
                },
                startAutoplay() {
                    this.stopAutoplay();
                    this.autoplayTimer = setInterval(() => {
                        if (this.canScrollRight) {
                            this.scrollByCard(1);
                        } else {
                            this.scrollContainer.scrollTo({ left: 0, behavior: 'smooth' });
                        }
                    }, 4000);
                },
                stopAutoplay() {
                    clearInterval(this.autoplayTimer);
                }
            }" class="relative">

                {{-- Arrow Left --}}
                <button x-show="canScrollLeft" x-transition @click="userScroll(-1)"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-6 z-10 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center text-gray-700 hover:bg-gray-50 hover:scale-110 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                {{-- Slider Track --}}
                <div x-ref="slider" class="flex gap-6 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-4"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    @foreach ($packages as $package)
                        <a href="{{ route('packages.show', $package->slug) }}"
                            class="group relative flex-shrink-0 w-[85%] md:w-[calc(50%-12px)] lg:w-[calc(25%-18px)] snap-start aspect-[3/4] rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-1">
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
                                <h3 class="text-2xl font-extrabold leading-tight mb-2 drop-shadow-lg">{{ $package->title }}
                                </h3>
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
                                <span
                                    class="block text-3xl font-extrabold text-amber-300 drop-shadow-lg">{{ $package->currency }}
                                    {{ number_format($package->price_from, 0, ',', '.') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Arrow Right --}}
                <button x-show="canScrollRight" x-transition @click="userScroll(1)"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-6 z-10 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center text-gray-700 hover:bg-gray-50 hover:scale-110 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <div class="text-center mt-10">
                <a href="{{ route('packages.index') }}"
                    class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl">
                    Ver todas las propuestas
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div
                    class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Destinos Exclusivos</h3>
                    <p class="text-gray-600 mb-4">Acceso a lugares únicos y experiencias personalizadas que no
                        encontrarás en
                        otro lugar.</p>
                    <a href="https://www.luopanviajes.tur.ar/" target="_blank"
                        class="text-amber-600 font-medium hover:text-amber-700 flex items-center gap-1 transition-colors">
                        Explorar destinos
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </div>
                <!-- Card 2 -->
                <div
                    class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Soporte 24/7</h3>
                    <p class="text-gray-600">Viaja con tranquilidad sabiendo que estamos siempre disponibles para
                        asistirte.</p>
                </div>
                <!-- Card 3 -->
                <div
                    class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Atención Personalizada</h3>
                    <p class="text-gray-600">Entendemos que cada viajero es único, por eso diseñamos cada viaje a tu
                        medida. </p>
                </div>
            </div>
        </div>

    </main>

    <!-- Consultas Modal -->
    <div x-show="showConsultasModal" class="fixed inset-0 z-[100] overflow-y-auto"
        x-on:lead-submitted.window="setTimeout(() => showConsultasModal = false, 2000)" style="display: none;">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="showConsultasModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showConsultasModal = false"></div>

            <!-- Modal Panel -->
            <div x-show="showConsultasModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                <div class="absolute right-4 top-4">
                    <button @click="showConsultasModal = false"
                        class="text-gray-400 hover:text-gray-500 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-8">
                    @livewire('public-lead-form')
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Assistant is now integrated in the hero section -->

    <!-- Floating Chat Assistant (Bottom Right) -->
    @livewire('public.chat-assistant', ['embedded' => false])

    <!-- Footer -->
    <footer class="bg-white/80 backdrop-blur-md border-t border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            @if($agencySettings?->logo_path)
                <img class="h-12 w-auto mx-auto mb-6" src="{{ asset('storage/' . $agencySettings->logo_path) }}" alt="{{ $agencySettings->company_name }}">
            @else
                <img class="h-12 w-auto mx-auto mb-6" src="{{ get_agency_logo() }}" alt="{{ config('app.name') }}">
            @endif
            
            @if($agencySettings?->address)
                <p class="text-gray-500 mb-2">{{ $agencySettings->address }}</p>
            @endif

            <div class="flex flex-col md:flex-row justify-center items-center gap-4 md:gap-8 text-gray-500 mb-8">
                @if($agencySettings?->contact_email)
                    <a href="mailto:{{ $agencySettings->contact_email }}" class="flex items-center gap-2 hover:text-amber-600 transition-colors">
                        <i class="ph ph-envelope text-xl"></i>
                        {{ $agencySettings->contact_email }}
                    </a>
                @endif
                @if($agencySettings?->contact_phone)
                    <a href="tel:{{ str_replace([' ', '-', '(', ')'], '', $agencySettings->contact_phone) }}" class="flex items-center gap-2 hover:text-amber-600 transition-colors">
                        <i class="ph ph-phone text-xl"></i>
                        {{ $agencySettings->contact_phone }}
                    </a>
                @endif
            </div>

            <div class="flex justify-center gap-8 mb-8">
                @if($agencySettings?->social_links)
                    @foreach($agencySettings->social_links as $link)
                        <a href="{{ format_social_link($link['url']) }}" target="_blank"
                            class="text-gray-400 hover:text-amber-600 transition-colors" title="{{ $link['platform'] }}">
                            <i class="ph-bold {{ $link['icon'] ?? 'ph-link' }} text-3xl"></i>
                        </a>
                    @endforeach
                @endif
            </div>
            
            @if($agencySettings?->footer_text)
                <p class="text-gray-500 mb-8 max-w-2xl mx-auto">{{ $agencySettings->footer_text }}</p>
            @endif

            <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $agencySettings->company_name ?? 'Omni-Agent' }}. Todos los derechos
                reservados.</p>
        </div>
    </footer>

    @if($agencySettings?->footer_scripts)
        {!! $agencySettings->footer_scripts !!}
    @endif
    @livewireScripts
</body>

</html>