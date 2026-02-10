<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Botón Nueva Consulta (Lead) --}}
            <a href="{{ \App\Filament\Admin\Resources\Leads\LeadResource::getUrl('create') }}"
                class="relative group block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-all duration-300 ring-2 ring-transparent hover:ring-primary-500">
                <div class="flex items-center gap-4">
                    <div
                        class="p-4 bg-primary-100 dark:bg-primary-900/30 rounded-lg group-hover:bg-primary-600 transition-colors duration-300">
                        <x-heroicon-o-chat-bubble-left-right
                            class="w-8 h-8 text-primary-600 dark:text-primary-400 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            Nueva Consulta
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Registrar interesado presencial o telefónico para iniciar seguimiento.
                        </p>
                    </div>
                </div>
            </a>

            {{-- Botón Buscar Cliente --}}
            <button x-on:click="$dispatch('open-global-search')"
                class="relative group block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-all duration-300 ring-2 ring-transparent hover:ring-secondary-500 text-left w-full">
                <div class="flex items-center gap-4">
                    <div
                        class="p-4 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg group-hover:bg-secondary-600 transition-colors duration-300">
                        <x-heroicon-o-magnifying-glass
                            class="w-8 h-8 text-secondary-600 dark:text-secondary-400 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-gray-100 group-hover:text-secondary-600 dark:group-hover:text-secondary-400 transition-colors">
                            Buscar Cliente
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Encontrar cliente existente para cargar venta o presupuesto.
                        </p>
                    </div>
                </div>
            </button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>