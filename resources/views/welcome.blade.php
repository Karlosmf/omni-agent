<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Luopan Viajes') }}</title>

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
</head>

<body class="antialiased min-h-screen relative flex flex-col"
    x-data="{ showConsultasModal: false, showWhatsAppDropdown: false }">

    <!-- Navbar / Header (Glassmorphism) -->
    <nav
        class="fixed top-0 w-full z-50 transition-all duration-300 bg-white/70 backdrop-blur-md border-b border-white/20 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <img class="h-12 w-auto dark:hidden" src="{{ asset('images/branding/logo-full.png') }}"
                        alt="Luopan Logo">
                    <img class="h-12 w-auto hidden dark:block" src="{{ asset('images/branding/logo-full-white.png') }}"
                        alt="Luopan Logo">
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
                        </div>
                    </div>

                    <a href="https://www.instagram.com/luopanviajes/" target="_blank"
                        class="text-gray-600 hover:text-pink-600 transition-colors">
                        <i class="ph-bold ph-instagram-logo text-2xl"></i>
                    </a>
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
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col pt-20">

        <!-- Hero Section -->
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
                        class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">Luopan</span>
                </h1>
                <p
                    class="text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 bg-white/60 p-4 rounded-xl backdrop-blur-sm border border-white/40 shadow-sm">
                    Planificamos tu próxima aventura con dedicación y experiencia. Desde escapadas locales hasta
                    destinos exóticos, hacemos realidad el viaje de tus sueños.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="https://www.luopanviajes.tur.ar/" target="_blank"
                        class="px-8 py-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl flex items-center justify-center gap-2">
                        <span>Planear mi viaje</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                    <a href="https://maps.app.goo.gl/njs4iW8KYS8owZhz6" target="_blank"
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
                </div>
            </div>

            <!-- Image & Lead Form -->
            <div class="w-full lg:w-1/2 relative flex flex-col items-center">
                <!-- Decorative Elements -->
                <div
                    class="absolute -top-10 -right-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob">
                </div>
                <div
                    class="absolute -bottom-10 -left-10 w-72 h-72 bg-amber-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000">
                </div>

                <!-- Hero Image Card -->
                <div class="relative w-full max-w-md transform transition-all hover:scale-[1.02] duration-500">
                    <div class="absolute inset-0 bg-gray-900 rounded-3xl rotate-3 opacity-20 blur-lg"></div>
                    <img src="{{ asset('images/landing/hero.png') }}" alt="Travel Destination"
                        class="relative rounded-3xl shadow-2xl object-cover h-[500px] w-full border-4 border-white">

                    <!-- Chat Assistant Integration -->
                    <div class="absolute -bottom-16 -left-4 md:-left-12 w-[calc(100%+2rem)] md:w-[110%] z-20"
                        id="chat-section">
                        @livewire('public.chat-assistant', ['embedded' => true])
                    </div>
                </div>
                <!-- Spacer for the form overlap -->
                <div class="h-24 hidden lg:block"></div>
            </div>

        </div>

        <!-- Features Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 mt-20">
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

    <!-- Footer -->
    <footer class="bg-white/80 backdrop-blur-md border-t border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img class="h-12 w-auto mx-auto mb-6 dark:hidden" src="{{ asset('images/branding/logo-full.png') }}" alt="Luopan Logo">
            <img class="h-12 w-auto mx-auto mb-6 hidden dark:block" src="{{ asset('images/branding/logo-full-white.png') }}" alt="Luopan Logo">
            <p class="text-gray-500 mb-6">Belgrano 843, Local A, Reconquista, Santa Fe 3560</p>
            <div class="flex justify-center gap-8 mb-8">
                <a href="https://www.facebook.com/luopanviajes" target="_blank"
                    class="text-gray-400 hover:text-blue-600 transition-colors" title="Facebook">
                    <i class="ph-bold ph-facebook-logo text-3xl"></i>
                </a>
                <a href="https://www.instagram.com/luopanviajes/" target="_blank"
                    class="text-gray-400 hover:text-pink-600 transition-colors" title="Instagram">
                    <i class="ph-bold ph-instagram-logo text-3xl"></i>
                </a>
                <a href="https://wa.link/16om0v" target="_blank"
                    class="text-gray-400 hover:text-green-600 transition-colors" title="WhatsApp">
                    <i class="ph-bold ph-whatsapp-logo text-3xl"></i>
                </a>
                <a href="mailto:belenzorzon@luopanviajes.tur.ar"
                    class="text-gray-400 hover:text-amber-600 transition-colors" title="Email">
                    <i class="ph-bold ph-envelope text-3xl"></i>
                </a>
            </div>
            <p class="text-xs text-gray-400">&copy; {{ date('Y') }} Luopan Viajes & Turismo. Todos los derechos
                reservados.</p>
        </div>
    </footer>

    @livewireScripts
</body>

</html>