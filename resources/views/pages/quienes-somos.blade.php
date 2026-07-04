<x-layouts.public>
    <!-- Hero Section -->
    <div class="relative pt-24 pb-16 lg:pt-32 lg:pb-20 overflow-hidden">
        <!-- Background Decorations (Blobs) -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-amber-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute top-32 -left-24 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob" style="animation-delay: 2s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <span class="inline-block px-4 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold tracking-wide uppercase mb-6 shadow-sm border border-amber-200">
                Sobre Nosotros
            </span>
            <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight mb-8">
                Descubre quiénes somos
            </h1>
            <div class="max-w-3xl mx-auto text-xl text-gray-600 leading-relaxed space-y-6">
                <p>
                    Somos una joven agencia de viajes, pero con integrantes que llevan años de experiencia trabajando en turismo, y la única que continúa especializándose en turismo receptivo en Goya – Corrientes. Además, ofrecemos experiencias a destinos nacionales e internacionales.
                </p>
                <p>
                    <strong>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }}</strong> es mucho más que una agencia de viajes. Somos tu compañero de aventuras, tu guía en la exploración de nuevos horizontes y tu conexión con la naturaleza y las culturas del mundo. Nuestros viajes te permitirán crear recuerdos inolvidables y descubrir la belleza del planeta.
                </p>
                <p class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600 italic mt-8 drop-shadow-sm">
                    "Al igual que un buen mate, tu mejor compañía."
                </p>
            </div>
        </div>
    </div>

    <!-- Cards Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Visión Card -->
            <div class="group bg-white/80 backdrop-blur-md p-8 rounded-3xl shadow-lg border border-white/50 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-amber-100 to-transparent rounded-bl-full opacity-50 -z-10 transition-transform duration-500 group-hover:scale-125"></div>
                <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 mb-6 shadow-sm border border-amber-200 transition-transform duration-300 group-hover:rotate-6">
                    <i class="ph-bold ph-eye text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Nuestra Visión</h3>
                <p class="text-gray-600 leading-relaxed">
                    Ser la agencia de viajes líder en experiencias turísticas auténticas y sostenibles, reconocida por conectar a los viajeros con la esencia de cada destino y promover un turismo responsable a nivel global.
                </p>
            </div>

            <!-- Misión Card -->
            <div class="group bg-white/80 backdrop-blur-md p-8 rounded-3xl shadow-lg border border-white/50 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-100 to-transparent rounded-bl-full opacity-50 -z-10 transition-transform duration-500 group-hover:scale-125"></div>
                <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mb-6 shadow-sm border border-orange-200 transition-transform duration-300 group-hover:-rotate-6">
                    <i class="ph-bold ph-bullseye text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Nuestra Misión</h3>
                <p class="text-gray-600 leading-relaxed">
                    Crear y ofrecer viajes personalizados y experiencias únicas que inspiren, eduquen y conecten a los viajeros con la naturaleza, las culturas locales y consigo mismos, fomentando el respeto por el medio ambiente y el desarrollo de las comunidades.
                </p>
            </div>

            <!-- Valores Card -->
            <div class="group bg-white/80 backdrop-blur-md p-8 rounded-3xl shadow-lg border border-white/50 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden md:col-span-2 lg:col-span-1">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-rose-100 to-transparent rounded-bl-full opacity-50 -z-10 transition-transform duration-500 group-hover:scale-125"></div>
                <div class="w-14 h-14 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 mb-6 shadow-sm border border-rose-200 transition-transform duration-300 group-hover:rotate-6">
                    <i class="ph-bold ph-heart text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Nuestros Valores</h3>
                <ul class="space-y-4 text-gray-600">
                    <li class="flex items-start gap-3">
                        <i class="ph-bold ph-check-circle text-rose-500 mt-1 text-xl flex-shrink-0"></i>
                        <span><strong>Autenticidad:</strong> Ofrecemos experiencias genuinas y auténticas, sumergiéndote en la cultura y la naturaleza de cada destino.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="ph-bold ph-check-circle text-rose-500 mt-1 text-xl flex-shrink-0"></i>
                        <span><strong>Sustentabilidad:</strong> Promovemos el turismo responsable, respetando el medio ambiente y las comunidades locales.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="ph-bold ph-check-circle text-rose-500 mt-1 text-xl flex-shrink-0"></i>
                        <span><strong>Experiencia:</strong> Creamos recuerdos inolvidables a través de viajes personalizados y llenos de aventuras.</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</x-layouts.public>
