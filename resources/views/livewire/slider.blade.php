<?php

use function Livewire\Volt\{state, mount};
use App\Models\JsonSlider;

state(['name', 'slides' => []]);

mount(function (string $name) {
    $this->name = $name;
    $slider = JsonSlider::find($name);
    $this->slides = $slider ? $slider->slides : [];
});

?>

<div class="relative w-full overflow-hidden rounded-2xl bg-gray-900 shadow-xl"
     x-data="{
         activeSlide: 0,
         slidesCount: {{ count($slides) }},
         interval: null,
         autoPlay() {
             this.interval = setInterval(() => {
                 this.next();
             }, 5000);
         },
         stopPlay() {
             clearInterval(this.interval);
         },
         next() {
             this.activeSlide = (this.activeSlide + 1) % this.slidesCount;
         },
         prev() {
             this.activeSlide = (this.activeSlide - 1 + this.slidesCount) % this.slidesCount;
         }
     }"
     x-init="autoPlay()"
     x-on:mouseenter="stopPlay()"
     x-on:mouseleave="autoPlay()"
>
    @if(empty($slides))
        <div class="flex flex-col items-center justify-center p-12 text-center text-gray-400 min-h-[300px]">
            <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-amber-500 mb-3" />
            <p class="text-sm font-semibold">El slider "{{ $name }}" no tiene diapositivas o no existe.</p>
        </div>
    @else
        <!-- Slides Wrapper -->
        <div class="relative min-h-[400px] md:min-h-[500px] lg:min-h-[600px] flex items-center">
            @foreach($slides as $index => $slide)
                <div x-show="activeSlide === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0 scale-105"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute inset-0 w-full h-full"
                >
                    <!-- Background Image with overlay -->
                    @php
                        $imageUrl = str_starts_with($slide['image_path'], 'http') 
                            ? $slide['image_path'] 
                            : asset('storage/' . $slide['image_path']);
                    @endphp
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $imageUrl }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-900/60 to-transparent"></div>

                    <!-- Slide Content -->
                    <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-12 h-full flex flex-col justify-center items-start text-white py-12">
                        <div class="max-w-2xl space-y-4 md:space-y-6">
                            @if(!empty($slide['subtitle']))
                                <span class="inline-block text-xs md:text-sm font-semibold uppercase tracking-wider text-primary-400 bg-primary-950/40 border border-primary-800/30 px-3 py-1 rounded-full">
                                    {{ $slide['subtitle'] }}
                                </span>
                            @endif

                            @if(!empty($slide['title']))
                                <h2 class="text-3xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                                    {{ $slide['title'] }}
                                </h2>
                            @endif

                            @if(!empty($slide['description']))
                                <p class="text-sm md:text-lg text-gray-300 font-light leading-relaxed">
                                    {{ $slide['description'] }}
                                </p>
                            @endif

                            <!-- CTA Buttons -->
                            @if(!empty($slide['cta_button_text']) || !empty($slide['sec_button_text']))
                                <div class="flex flex-wrap gap-4 pt-2">
                                    @if(!empty($slide['cta_button_text']))
                                        <a href="{{ $slide['cta_button_url'] ?? '#' }}"
                                           class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm md:text-base font-medium rounded-xl text-white bg-primary-600 hover:bg-primary-500 shadow-lg hover:shadow-primary-500/20 transform hover:-translate-y-0.5 transition-all duration-150"
                                        >
                                            {{ $slide['cta_button_text'] }}
                                        </a>
                                    @endif

                                    @if(!empty($slide['sec_button_text']))
                                        <a href="{{ $slide['sec_button_url'] ?? '#' }}"
                                           class="inline-flex items-center justify-center px-6 py-3 border border-gray-700 hover:border-gray-500 text-sm md:text-base font-medium rounded-xl text-gray-300 hover:text-white bg-gray-950/40 hover:bg-gray-900/40 backdrop-blur-xs transition-all duration-150"
                                        >
                                            {{ $slide['sec_button_text'] }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Controls (Prev / Next Buttons) -->
        @if(count($slides) > 1)
            <button x-on:click="prev()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2.5 rounded-full bg-black/30 text-white/80 hover:text-white hover:bg-black/50 border border-white/10 backdrop-blur-xs transition-all duration-150"
                    aria-label="Diapositiva anterior"
            >
                <x-heroicon-o-chevron-left class="w-6 h-6" />
            </button>

            <button x-on:click="next()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2.5 rounded-full bg-black/30 text-white/80 hover:text-white hover:bg-black/50 border border-white/10 backdrop-blur-xs transition-all duration-150"
                    aria-label="Siguiente diapositiva"
            >
                <x-heroicon-o-chevron-right class="w-6 h-6" />
            </button>

            <!-- Indicators (Dots) -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                @foreach($slides as $index => $_)
                    <button x-on:click="activeSlide = {{ $index }}"
                            class="h-2 rounded-full transition-all duration-300"
                            x-bind:class="activeSlide === {{ $index }} ? 'w-8 bg-primary-500' : 'w-2 bg-white/40 hover:bg-white/70'"
                            aria-label="Ir a diapositiva {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    @endif
</div>
