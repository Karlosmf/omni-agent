<div
    class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md p-6 rounded-2xl shadow-xl border border-white/20 dark:border-zinc-700/50">
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
        {{ $this->form }}

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