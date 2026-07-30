<x-layouts.public>

        <!-- Hero Section (Static with Photo Stack) -->
        @php
            $heroStack = \App\Models\JsonSlider::find('hero-stack');
            $heroImages = $heroStack
                ? collect($heroStack->slides)->map(fn($s) => str_starts_with($s['image_path'], 'http') ? $s['image_path'] : (str_starts_with($s['image_path'], 'predefined/') ? asset('storage/' . $s['image_path']) : asset('uploads/' . $s['image_path'])))->toArray()
                : [];
        @endphp
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
                        class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">{{ $agencySettings?->company_name ?? 'Omni-Agent' }}</span>
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

            <!-- Photo Stack Card -->
            <div class="w-full lg:w-1/2 relative flex flex-col items-center">
                <div class="relative w-full max-w-md transform transition-all hover:scale-[1.02] duration-500">
                    <!-- Decorative Elements -->
                    <div
                        class="absolute -top-10 -right-10 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob">
                    </div>
                    <div
                        class="absolute -bottom-10 -left-10 w-64 h-64 bg-amber-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000">
                    </div>

                    <div class="absolute inset-0 bg-gray-900 rounded-3xl rotate-3 opacity-20 blur-lg"></div>
                    <div class="relative rounded-3xl shadow-2xl overflow-hidden border-4 border-white aspect-[4/5]">
                        <x-photo-stack :images="$heroImages" />
                    </div>
                </div>
                <!-- Spacer -->
                <div class="h-48 hidden lg:block"></div>
            </div>
        </div>

        <!-- Promo Slider (JSON) -->
        @php $promoSlider = \App\Models\JsonSlider::find('promo'); @endphp
        @if($promoSlider && !empty($promoSlider->slides))
            <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-12">
                <!-- Glowing background / Reflection effect -->
                <div class="absolute inset-0 -bottom-8 bg-gradient-to-r from-amber-400 via-orange-500 to-pink-500 rounded-3xl blur-3xl opacity-20 -z-10 transform scale-95 translate-y-4"></div>
                <livewire:slider name="promo" borderStyle="promo" />
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
                                <img src="{{ str_starts_with($package->cover_image, 'http') ? $package->cover_image : asset('uploads/' . $package->cover_image) }}"
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
                                    {{ $package->destination }} · {{ $package->nights > 0 ? $package->nights . ' noches' : 'Full Day' }}
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
                                <span class="block text-xs font-medium text-white/90 drop-shadow-md mt-0.5">{{ $package->price_basis?->label() }}</span>
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
                    <a href="#packages" 
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
</x-layouts.public>
