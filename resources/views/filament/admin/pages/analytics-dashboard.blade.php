<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Funnel de Conversión --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-funnel" class="w-6 h-6 text-amber-500" />
                Funnel de Ventas
            </h2>
            
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                {{-- Etapa 1: Leads --}}
                <div class="w-full md:w-1/3 text-center relative group">
                    <div class="bg-gray-50 dark:bg-white/5 rounded-2xl p-6 border-2 border-transparent group-hover:border-amber-200 transition-colors relative z-10">
                        <div class="text-4xl font-black text-gray-900 dark:text-white mb-1">{{ $totalLeads }}</div>
                        <div class="text-sm font-semibold text-gray-500 uppercase tracking-widest">Leads Capturados</div>
                    </div>
                </div>
                
                {{-- Arrow 1 --}}
                <div class="hidden md:flex flex-col items-center shrink-0 w-24">
                    <div class="text-xs font-bold text-amber-600 dark:text-amber-400 mb-2">{{ $cotizacionRate }}%</div>
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
                <div class="md:hidden text-amber-600 font-bold text-sm">{{ $cotizacionRate }}%</div>

                {{-- Etapa 2: Cotizaciones --}}
                <div class="w-full md:w-1/3 text-center relative group">
                    <div class="bg-amber-50 dark:bg-amber-500/10 rounded-2xl p-6 border-2 border-transparent group-hover:border-amber-300 transition-colors relative z-10">
                        <div class="text-4xl font-black text-amber-700 dark:text-amber-400 mb-1">{{ $totalCotizados }}</div>
                        <div class="text-sm font-semibold text-amber-700/70 dark:text-amber-400/70 uppercase tracking-widest">Cotizaciones Env.</div>
                    </div>
                </div>

                {{-- Arrow 2 --}}
                <div class="hidden md:flex flex-col items-center shrink-0 w-24">
                    <div class="text-xs font-bold text-green-600 dark:text-green-400 mb-2">{{ $confirmacionRate }}%</div>
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
                <div class="md:hidden text-green-600 font-bold text-sm">{{ $confirmacionRate }}%</div>

                {{-- Etapa 3: Confirmados --}}
                <div class="w-full md:w-1/3 text-center relative group">
                    <div class="bg-green-50 dark:bg-green-500/10 rounded-2xl p-6 border-2 border-transparent group-hover:border-green-300 transition-colors relative z-10">
                        <div class="text-4xl font-black text-green-700 dark:text-green-400 mb-1">{{ $totalConfirmados }}</div>
                        <div class="text-sm font-semibold text-green-700/70 dark:text-green-400/70 uppercase tracking-widest">Viajes Vendidos</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center text-sm text-gray-500">
                Conversión Total (Leads a Ventas): <strong class="text-gray-900 dark:text-white">{{ $totalLeads > 0 ? round(($totalConfirmados / $totalLeads) * 100, 1) : 0 }}%</strong>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Fuentes de Leads --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Orígenes de Consultas (Sources)</h3>
                @if($sources->isEmpty())
                    <p class="text-gray-500 text-sm italic">No hay datos suficientes.</p>
                @else
                    <ul class="space-y-4">
                        @foreach($sources as $source)
                            @php
                                $percent = $totalLeads > 0 ? round(($source->total / $totalLeads) * 100) : 0;
                            @endphp
                            <li>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300 uppercase">{{ str_replace('_', ' ', $source->source ?? 'Desconocido') }}</span>
                                    <span class="text-gray-500 font-bold">{{ $percent }}% ({{ $source->total }})</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                                    <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Top Productos --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Top Paquetes Vendidos</h3>
                @if($topProducts->isEmpty())
                    <p class="text-gray-500 text-sm italic">No hay datos suficientes de ventas vinculadas a paquetes.</p>
                @else
                    <div class="space-y-4">
                        @foreach($topProducts as $index => $booking)
                            <div class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black shrink-0">
                                    #{{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $booking->travelPackage?->title ?? 'Paquete Desconocido' }}
                                    </h4>
                                    <p class="text-xs text-gray-500 truncate">{{ $booking->travelPackage?->destination ?? '' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-lg font-black text-gray-900 dark:text-white">{{ $booking->total }}</span>
                                    <span class="text-xs text-gray-500 block">ventas</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
