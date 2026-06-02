@props(['sliders'])

@if($sliders->isNotEmpty())
<div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-12">
    <!-- Glowing background / Reflection effect -->
    <div class="absolute inset-0 -bottom-8 bg-gradient-to-r from-amber-400 via-orange-500 to-pink-500 rounded-3xl blur-3xl opacity-20 -z-10 transform scale-95 translate-y-4"></div>
    
    <div 
        x-data="{ 
            activeSlide: 0, 
            slides: {{ $sliders->count() }},
            autoplay() {
                if (this.slides <= 1) return;
                setInterval(() => {
                    this.activeSlide = (this.activeSlide + 1) % this.slides;
                }, 5000);
            }
        }" 
        x-init="autoplay()"
        class="relative w-full rounded-2xl overflow-hidden shadow-[0_20px_50px_-12px_rgba(0,0,0,0.5)] border border-white/10 group bg-gray-900"
        style="aspect-ratio: 4/1; min-height: 200px;"
    >
        <!-- Slides -->
        @foreach($sliders as $index => $slide)
            <div 
                x-show="activeSlide === {{ $index }}"
                x-transition:enter="transition-opacity ease-out duration-700"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-700"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 w-full h-full"
            >
                <!-- Image -->
                <img 
                    src="{{ str_starts_with($slide->image_path, 'http') ? $slide->image_path : asset('storage/' . $slide->image_path) }}" 
                    alt="{{ $slide->title }}"
                    class="absolute inset-0 w-full h-full object-cover transform transition-transform duration-10000 hover:scale-105"
                >
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/40 to-transparent"></div>
                
                <!-- Content -->
                <div class="absolute inset-0 flex items-center justify-start p-8 md:p-12">
                    <div class="max-w-xl text-left">
                        @if($slide->subtitle)
                            <span class="inline-block px-3 py-1 bg-amber-500/90 text-white text-xs sm:text-sm font-bold tracking-wider uppercase rounded-full mb-3 backdrop-blur-sm shadow-sm">
                                {{ $slide->subtitle }}
                            </span>
                        @endif
                        <h3 class="text-2xl md:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight drop-shadow-md">
                            {{ $slide->title }}
                        </h3>
                        @if($slide->cta_button_text && $slide->cta_button_url)
                            <a href="{{ $slide->cta_button_url }}" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-900 font-bold rounded-xl shadow-lg hover:bg-gray-50 hover:scale-105 transition-all duration-300">
                                {{ $slide->cta_button_text }}
                                <i class="ph-bold ph-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Navigation Dots -->
        @if($sliders->count() > 1)
            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-10">
                @foreach($sliders as $index => $slide)
                    <button 
                        @click="activeSlide = {{ $index }}"
                        class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="activeSlide === {{ $index }} ? 'bg-amber-500 w-6' : 'bg-white/50 hover:bg-white'"
                        aria-label="Go to slide {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
            
            <!-- Arrows -->
            <button 
                @click="activeSlide = activeSlide === 0 ? slides - 1 : activeSlide - 1"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-md flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 z-10"
            >
                <i class="ph-bold ph-caret-left text-xl"></i>
            </button>
            <button 
                @click="activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-md flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 z-10"
            >
                <i class="ph-bold ph-caret-right text-xl"></i>
            </button>
        @endif
    </div>
</div>
@endif
