<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Botón Nueva Consulta (Lead) --}}
            <a href="{{ \App\Filament\Admin\Resources\Leads\LeadResource::getUrl('create') }}"
                class="relative group block p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-all duration-300 ring-2 ring-transparent hover:ring-primary-500">
                <div class="flex items-center gap-4">
                    <div
                        class="p-4 bg-primary-100 dark:bg-primary-900/30 rounded-lg group-hover:bg-primary-600 transition-colors duration-300 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" style="width: 32px; height: 32px;"
                            class="text-primary-600 dark:text-primary-400 group-hover:text-white transition-colors duration-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
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
                        class="p-4 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg group-hover:bg-secondary-600 transition-colors duration-300 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" style="width: 32px; height: 32px;"
                            class="text-secondary-600 dark:text-secondary-400 group-hover:text-white transition-colors duration-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
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