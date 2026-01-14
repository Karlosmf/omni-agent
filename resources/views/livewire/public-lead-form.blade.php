<div
    class="bg-white/90 dark:bg-zinc-800/90 backdrop-blur-sm p-6 rounded-2xl shadow-xl border border-white/20 dark:border-zinc-700/50">
    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">¿Consultas?</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Dejanos tus datos y te contactaremos para planear tu
        próximo viaje.</p>

    @if($success)
        <div
            class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-4 rounded-lg mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>¡Gracias! Tu consulta fue enviada con éxito.</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
            <input wire:model="name" type="text" id="name"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-white transition-all outline-none"
                placeholder="Tu nombre">
            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <input wire:model="email" type="email" id="email"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-white transition-all outline-none"
                placeholder="tu@email.com">
            @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono
                (Opcional)</label>
            <input wire:model="phone" type="tel" id="phone"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-white transition-all outline-none"
                placeholder="+54 9 ...">
            @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje</label>
            <textarea wire:model="message" id="message" rows="3"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-white transition-all outline-none resize-none"
                placeholder="Contanos qué estás buscando..."></textarea>
            @error('message') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
            class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-medium py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all flex justify-center items-center gap-2 group">
            <span wire:loading.remove>Enviar Consulta</span>
            <span wire:loading class="animate-pulse">Enviando...</span>
            <svg wire:loading.remove class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3">
                </path>
            </svg>
        </button>
    </form>
</div>