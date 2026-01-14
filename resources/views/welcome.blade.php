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

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f2f5;
            background-image: url("{{ asset('images/landing/bg-pattern.png') }}");
            background-repeat: repeat;
            background-size: 400px;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @livewireStyles
</head>
<body class="antialiased min-h-screen relative flex flex-col">

    <!-- Navbar / Header (Glassmorphism) -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 bg-white/70 backdrop-blur-md border-b border-white/20 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <img class="h-10 w-auto" src="{{ asset('images/branding/logo-icon.png') }}" alt="Luopan Icon">
                    <span class="text-2xl font-bold tracking-tight text-gray-900">LUØPAN</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#leads-form" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Consultas</a>
                    <a href="https://wa.me/5493482300052" target="_blank" class="text-gray-600 hover:text-green-600 font-medium transition-colors flex items-center gap-1">
                        Chat
                    </a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/admin') }}" class="px-5 py-2.5 rounded-full bg-gray-900 text-white font-medium hover:bg-gray-800 transition-all shadow-md hover:shadow-lg">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-full bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-md hover:shadow-lg">Ingresar</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col pt-20">
        
        <!-- Hero Section -->
        <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
            
            <!-- Text Content -->
            <div class="w-full lg:w-1/2 space-y-8 text-center lg:text-left z-10">
                <div class="inline-block px-4 py-1.5 rounded-full bg-amber-100/80 text-amber-800 font-medium text-sm backdrop-blur-sm border border-amber-200">
                    ✈️ Tu compañera de viajes
                </div>
                <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight">
                    Descubre el mundo con <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">Luopan</span>
                </h1>
                <p class="text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 bg-white/60 p-4 rounded-xl backdrop-blur-sm border border-white/40 shadow-sm">
                    Planificamos tu próxima aventura con dedicación y experiencia. Desde escapadas locales hasta destinos exóticos, hacemos realidad el viaje de tus sueños.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#leads-form" class="px-8 py-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl flex items-center justify-center gap-2">
                        <span>Planear mi viaje</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="https://maps.app.goo.gl/kMzr6pzgD1z4tP5h8" target="_blank" class="px-8 py-4 rounded-xl bg-white text-gray-900 font-semibold hover:bg-gray-50 border border-gray-200 transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                        <span>Ver ubicación</span>
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Image & Lead Form -->
            <div class="w-full lg:w-1/2 relative flex flex-col items-center">
                <!-- Decorative Elements -->
                <div class="absolute -top-10 -right-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
                <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-amber-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>

                <!-- Hero Image Card -->
                <div class="relative w-full max-w-md transform transition-all hover:scale-[1.02] duration-500">
                    <div class="absolute inset-0 bg-gray-900 rounded-3xl rotate-3 opacity-20 blur-lg"></div>
                    <img src="{{ asset('images/landing/hero.png') }}" alt="Travel Destination" class="relative rounded-3xl shadow-2xl object-cover h-[500px] w-full border-4 border-white">
                    
                    <!-- Floating Leads Form (Overlapping the image slightly or below it depending on mobile) -->
                    <div class="absolute -bottom-16 -left-4 md:-left-12 w-[calc(100%+2rem)] md:w-[110%] z-20" id="leads-form">
                        @livewire('public-lead-form')
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
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Destinos Exclusivos</h3>
                    <p class="text-gray-600">Acceso a lugares únicos y experiencias personalizadas que no encontrarás en otro lugar.</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Soporte 24/7</h3>
                    <p class="text-gray-600">Viaja con tranquilidad sabiendo que estamos siempre disponibles para asistirte.</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-white/50 hover:shadow-xl transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Atención Personalizada</h3>
                    <p class="text-gray-600">Entendemos que cada viajero es único, por eso diseñamos cada viaje a tu medida. </p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white/80 backdrop-blur-md border-t border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img class="h-12 w-auto mx-auto mb-6" src="{{ asset('images/branding/logo-full.png') }}" alt="Luopan Logo">
            <p class="text-gray-500 mb-6">Belgrano 843, Local A, Reconquista, Santa Fe 3560</p>
            <div class="flex justify-center gap-6 mb-8">
                <a href="#" class="text-gray-400 hover:text-amber-600 transition-colors"><span class="sr-only">Facebook</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                <a href="#" class="text-gray-400 hover:text-amber-600 transition-colors"><span class="sr-only">Instagram</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772 4.902 4.902 0 011.772-1.153c.636-.247 1.363-.416 2.427-.465C9.673 2.013 10.03 2 12.484 2h.058v.02zm-3.21 1.725c-2.126 0-2.388.01-3.226.046-.777.034-1.196.155-1.477.265-.403.156-.757.41-1.04.693-.284.283-.537.637-.693 1.04-.11.281-.231.7-.265 1.477-.036.837-.045 1.099-.045 3.225s.01 2.388.046 3.226c.034.777.155 1.196.265 1.477.156.403.41.757.693 1.04.283.284.637.537 1.04.693.281.11.7.231 1.477.265.837.036 1.099.045 3.225.045 2.126 0 2.388-.01 3.226-.046.777-.034 1.196-.155 1.477-.265.403-.156.757-.41 1.04-.693.284-.283.537-.637.693-1.04.11-.281.231-.7.265-1.477.036-.837.045-1.099.045-3.225s-.01-2.388-.046-3.226c-.034-.777-.155-1.196-.265-1.477-.156-.403-.41-.757-.693-1.04-.283-.284-.637-.537-1.04-.693-.281-.11-.7-.231-1.477-.265-.827-.036-1.104-.045-3.21-.045zm3.21 3.486a4.266 4.266 0 110 8.532 4.266 4.266 0 010-8.532zm0 1.764a2.502 2.502 0 100 5.004 2.502 2.502 0 000-5.004zm5.09-3.722a1.058 1.058 0 110 2.116 1.058 1.058 0 010-2.116z" clip-rule="evenodd" /></svg></a>
            </div>
            <p class="text-xs text-gray-400">&copy; {{ date('Y') }} Luopan Viajes & Turismo. All rights reserved.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>