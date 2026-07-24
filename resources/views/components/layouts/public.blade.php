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
    @filamentStyles
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
                <div class="flex-shrink-0 flex items-center h-full gap-3">
                    <a href="{{ url('/') }}" class="flex items-center h-full gap-3">
                        @if($agencySettings?->logotipo_path || $agencySettings?->isotipo_path)
                            @if($agencySettings?->logotipo_path)
                                <img class="h-full w-auto object-contain hidden md:block" src="{{ get_agency_logotipo_url() }}" alt="{{ $agencySettings->company_name }}">
                            @endif
                            @if($agencySettings?->isotipo_path)
                                <img class="h-full w-auto object-contain {{ $agencySettings?->logotipo_path ? 'md:hidden' : '' }}" src="{{ get_agency_isotipo_url() }}" alt="{{ $agencySettings->company_name }}">
                            @endif
                        @else
                            <span class="text-xl font-bold text-gray-900">{{ $agencySettings?->company_name ?? config('app.name') }}</span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6 lg:space-x-8">
                    <a href="{{ url('/') }}" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Inicio</a>
                    <a href="{{ route('pages.quienes-somos') }}" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Quiénes Somos</a>
                    <div class="relative group" @click.away="showPolicies = false" x-data="{ showPolicies: false }">
                        <button @click="showPolicies = !showPolicies" class="text-gray-600 hover:text-amber-600 font-medium transition-colors flex items-center gap-1">
                            Políticas
                            <svg class="w-4 h-4 transition-transform duration-200" :class="showPolicies ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="showPolicies" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 mt-2 w-48 rounded-xl bg-white shadow-xl border border-gray-100 py-2 z-50 overflow-hidden" style="display: none;">
                            <a href="{{ route('pages.privacidad') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-600">Privacidad</a>
                            <a href="{{ route('pages.cookies') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-600">Cookies</a>
                        </div>
                    </div>
                    <button @click="showConsultasModal = true"
                        class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Consultas</button>

                    @php
                        $whatsappLinks = collect($agencySettings?->social_links ?? [])
                            ->filter(fn($link) => 
                                str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') || 
                                str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
                            );
                        $hasPhone = !empty($agencySettings?->contact_phone);
                    @endphp
                    @if($whatsappLinks->isNotEmpty() || $hasPhone)
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
                                    <a href="https://wa.me/{{ str_replace([' ', '-', '(', ')', '+'], '', $agencySettings->contact_phone) }}" target="_blank"
                                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                                        <div
                                            class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold font-mono">
                                            W</div>
                                        <span class="font-medium">WhatsApp</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

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
                <a href="{{ url('/') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-amber-600 hover:bg-amber-50">Inicio</a>
                <a href="{{ route('pages.quienes-somos') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-amber-600 hover:bg-amber-50">Quiénes Somos</a>
                <div x-data="{ open: false }">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-amber-600 hover:bg-amber-50">
                        Políticas
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" class="pl-4 space-y-1 mt-1" style="display: none;">
                        <a href="{{ route('pages.privacidad') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-amber-600 hover:bg-amber-50">Privacidad</a>
                        <a href="{{ route('pages.cookies') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-amber-600 hover:bg-amber-50">Cookies</a>
                    </div>
                </div>
                <button @click="showConsultasModal = true; showMobileMenu = false"
                    class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-amber-600 hover:bg-amber-50">
                    Consultas
                </button>

                @if($whatsappLinks->isNotEmpty() || $hasPhone)
                    <!-- WhatsApp Mobile Group -->
                    <div class="px-3 py-2">
                        <span
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">WhatsApp</span>
                        <div class="flex flex-col gap-2 pl-2">
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
                                <a href="https://wa.me/{{ str_replace([' ', '-', '(', ')', '+'], '', $agencySettings->contact_phone) }}" target="_blank"
                                    class="flex items-center gap-2 text-gray-700 hover:text-green-600">
                                    <div
                                        class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-[10px] font-bold">
                                        W</div>
                                    <span>WhatsApp</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

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
        {{ $slot }}

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
            @if($agencySettings?->logotipo_path)
                <img class="h-12 w-auto mx-auto mb-6" src="{{ get_agency_logotipo_url() }}" alt="{{ $agencySettings->company_name }}">
            @elseif($agencySettings?->isotipo_path)
                <img class="h-12 w-auto mx-auto mb-6" src="{{ get_agency_isotipo_url() }}" alt="{{ $agencySettings->company_name }}">
            @else
                <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ $agencySettings?->company_name ?? config('app.name') }}</h2>
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

            
            <div class="flex flex-col md:flex-row justify-center items-center gap-4 md:gap-8 text-gray-500 mb-8">
                <a href="{{ route('pages.quienes-somos') }}" class="hover:text-amber-600 transition-colors">Quiénes Somos</a>
                <a href="{{ route('pages.privacidad') }}" class="hover:text-amber-600 transition-colors">Políticas de Privacidad</a>
                <a href="{{ route('pages.cookies') }}" class="hover:text-amber-600 transition-colors">Políticas de Cookies</a>
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

            <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $agencySettings?->company_name ?? 'Omni-Agent' }}. Todos los derechos
                reservados.</p>
        </div>
    </footer>

    @if($agencySettings?->footer_scripts)
        {!! $agencySettings->footer_scripts !!}
    @endif
    @livewireScripts
    @filamentScripts
</body>

</html>