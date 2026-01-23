<div class="filament-widget">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" style="width: 20px; height: 20px;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Próximos Vencimientos (30 días)</span>
            </div>
        </x-slot>

        @php
            $trips = $this->getUpcomingTrips();
        @endphp

        @if(count($trips) > 0)
            <div class="space-y-3">
                @foreach($trips as $trip)
                    <div class="flex items-center justify-between p-3 rounded-lg border dark:border-gray-700
                                        @if($trip['days_until'] <= 7) bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800
                                        @elseif($trip['days_until'] <= 15) bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
                                        @else bg-gray-50 dark:bg-gray-800
                                        @endif">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $trip['file_number'] }} - {{ $trip['holder_name'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Viaje: {{ $trip['travel_date']->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold
                                                @if($trip['days_until'] <= 7) text-red-600 dark:text-red-400
                                                @elseif($trip['days_until'] <= 15) text-yellow-600 dark:text-yellow-400
                                                @else text-gray-600 dark:text-gray-400
                                                @endif">
                                {{ abs($trip['days_until']) }} días
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $trip['status']->getLabel() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                No hay viajes programados en los próximos 30 días
            </div>
        @endif
    </x-filament::section>
</div>