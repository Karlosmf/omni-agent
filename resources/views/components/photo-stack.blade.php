@props(['images' => [
    'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?q=80&w=800&auto=format&fit=crop', // Maldives
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&auto=format&fit=crop', // Swiss Alps
    'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?q=80&w=800&auto=format&fit=crop', // Paris
    'https://images.unsplash.com/photo-1537996194471-e657df975ab4?q=80&w=800&auto=format&fit=crop', // Bali
    'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?q=80&w=800&auto=format&fit=crop', // Santorini
]])

<div x-data="{ 
    active: 0,
    images: @js($images),
    next() { this.active = (this.active + 1) % this.images.length },
    prev() { this.active = (this.active - 1 + this.images.length) % this.images.length },
    init() {
        setInterval(() => this.next(), 5000)
    }
}" class="relative w-full h-full group">
    
    <!-- Image Stack -->
    <template x-for="(img, index) in images" :key="index">
        <div x-show="active === index"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 scale-110 rotate-3"
             x-transition:enter-end="opacity-100 scale-100 rotate-0"
             x-transition:leave="transition ease-in duration-1000"
             x-transition:leave-start="opacity-100 scale-100 rotate-0"
             x-transition:leave-end="opacity-0 scale-90 -rotate-3"
             class="absolute inset-0">
            <img :src="img" class="w-full h-full object-cover">
            
            <!-- Instagram Style Overlay -->
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-6 text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center">
                        <i class="ph ph-user text-sm"></i>
                    </div>
                    <span class="font-medium text-sm">{{ $agencySettings?->company_name ?? config('app.name', 'nuestra agencia') }}</span>
                </div>
            </div>
        </div>
    </template>

    <!-- Navigation -->
    <div class="absolute inset-0 flex items-center justify-between px-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        <button @click="prev()" class="p-2 rounded-full bg-black/20 backdrop-blur-md text-white hover:bg-black/40 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="next()" class="p-2 rounded-full bg-black/20 backdrop-blur-md text-white hover:bg-black/40 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Dots -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 p-2 rounded-full bg-black/10 backdrop-blur-sm">
        <template x-for="(img, index) in images" :key="index">
            <div :class="active === index ? 'w-2 h-2 bg-white' : 'w-2 h-2 bg-white/40'" 
                 class="rounded-full transition-all duration-300 cursor-pointer"
                 @click="active = index"></div>
        </template>
    </div>
</div>
