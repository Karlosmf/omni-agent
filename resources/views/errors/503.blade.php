<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ get_agency_favicon() }}">
    <title>Sitio en Mantenimiento | {{ $agencySettings?->company_name ?? 'Agencia' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-[#0b0f19] text-gray-100 font-sans min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Glow Elements -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -z-10 animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl -z-10 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="w-full max-w-lg bg-gray-900/60 backdrop-blur-xl border border-gray-800 rounded-3xl p-8 md:p-12 text-center shadow-2xl relative">
        
        <!-- Logo -->
        <div class="mb-8 flex justify-center">
            @if($agencySettings?->logotipo_path)
                <img class="h-14 w-auto object-contain" src="{{ get_agency_logotipo_url() }}" alt="{{ $agencySettings->company_name }}">
            @elseif($agencySettings?->isotipo_path)
                <img class="h-14 w-auto object-contain" src="{{ get_agency_isotipo_url() }}" alt="{{ $agencySettings->company_name }}">
            @else
                <span class="text-2xl font-bold tracking-tight text-white">
                    {{ $agencySettings?->company_name ?? 'Nuestra Agencia' }}
                </span>
            @endif
        </div>

        <!-- Maintenance Icon -->
        <div class="w-20 h-20 bg-amber-500/10 border border-amber-500/30 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-8 animate-bounce">
            <i class="ph ph-wrench text-4xl"></i>
        </div>

        <!-- Title & Subtitle -->
        <h1 class="text-3xl font-bold text-white mb-4">Estamos en mantenimiento</h1>
        <p class="text-gray-400 text-base leading-relaxed mb-8">
            Estamos realizando mejoras programadas en nuestra plataforma para brindarte un mejor servicio. 
            Disculpa las molestias, ¡volveremos muy pronto!
        </p>

        <!-- Separation Line -->
        <div class="border-t border-gray-800/80 my-6"></div>

        <!-- Footer / Bypass & Admin Link -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
            <div>
                &copy; {{ date('Y') }} {{ $agencySettings?->company_name ?? 'Agencia' }}.
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Administrative Bypass Form Dialog Trigger (optional/subtle) -->
                <a href="{{ url('/admin') }}" class="flex items-center gap-1 hover:text-amber-500 transition-colors">
                    <i class="ph ph-shield-check text-sm"></i>
                    Acceso Administrativo
                </a>
            </div>
        </div>

        <!-- Subtle Bypass input trigger -->
        <div x-data="{ open: false }" class="mt-4 text-left">
            <button @click="open = !open" class="text-[10px] text-gray-700 hover:text-gray-500 transition-colors block mx-auto">
                Bypass por Clave
            </button>
            <div x-show="open" class="mt-2" style="display: none;" :style="open ? 'display: block;' : 'display: none;'">
                <form action="{{ url('/') }}" method="GET" class="flex gap-2 max-w-xs mx-auto">
                    <input type="text" name="bypass" placeholder="Ingresa clave de bypass..." class="flex-1 bg-gray-950 border border-gray-800 rounded-lg px-3 py-1.5 text-xs text-gray-300 focus:outline-none focus:border-amber-500">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-950 font-bold px-3 py-1.5 rounded-lg text-xs transition-colors">
                        Bypass
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- AlpineJS for the key bypass UI -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
