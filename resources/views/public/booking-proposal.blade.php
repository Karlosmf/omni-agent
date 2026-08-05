<x-layouts.guest title="Tu Propuesta · {{ $booking->destination ?? 'Viaje' }}">
    @php
        $settings = \App\Models\AgencySetting::first();
        $expired = $booking->isExpired();
        
        $whatsappLinks = collect($settings?->social_links ?? [])
            ->filter(fn($l) => str_contains(strtolower($l['platform'] ?? ''), 'whatsapp')
                            || str_contains(strtolower($l['icon'] ?? ''), 'whatsapp'));
        $firstWhatsapp = $whatsappLinks->first();
    @endphp

    <div class="min-h-screen bg-gray-50">

        {{-- Hero --}}
        <div class="relative h-[50vh] md:h-[60vh] overflow-hidden bg-gray-900">
            {{-- Background --}}
            <div class="absolute inset-0 w-full h-full" style="background: linear-gradient(135deg, var(--color-primary, #0f172a), var(--color-secondary, #3b82f6));"></div>
            
            {{-- Pattern overlay --}}
            <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            {{-- Dark overlay (bottom) --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10"></div>

            {{-- Title Over Image --}}
            <div class="absolute bottom-0 left-0 right-0 z-20 p-8 md:p-16">
                <div class="max-w-7xl mx-auto">
                    @if($settings?->logotipo_path || $settings?->isotipo_path)
                        <img src="{{ $settings?->logotipo_path ? get_agency_logotipo_url() : get_agency_isotipo_url() }}" alt="{{ $settings?->company_name }}" class="h-12 w-auto mb-6 drop-shadow-md bg-white/10 rounded-lg p-2 backdrop-blur-sm">
                    @else
                        <div class="text-white/80 font-medium tracking-widest uppercase mb-4">{{ $settings?->company_name ?? config('app.name') }}</div>
                    @endif

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-4 drop-shadow-lg">
                        {{ $booking->destination ?? 'Propuesta Exclusiva' }}
                    </h1>
                    <p class="text-lg text-white/90 flex items-center gap-3 flex-wrap drop-shadow">
                        <span class="flex items-center gap-1.5 font-medium">
                            <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Diseñado para: {{ $booking->holder_name }}
                        </span>
                        <span class="text-white/40">·</span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $booking->travel_date ? $booking->travel_date->format('d/m/Y') : 'Fecha a confirmar' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Share Button --}}
            <div class="absolute top-6 right-6 z-30 flex gap-2">
                <button onclick="window.print()" class="p-3 bg-white/20 backdrop-blur-md rounded-full text-white hover:bg-white/30 transition-colors tooltip" data-tip="Imprimir PDF">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>
                <button onclick="navigator.share ? navigator.share({ title: 'Tu propuesta de viaje', url: window.location.href }) : null" class="p-3 bg-white/20 backdrop-blur-md rounded-full text-white hover:bg-white/30 transition-colors tooltip" data-tip="Compartir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                {{-- Main Content (col-span-2) --}}
                <div class="md:col-span-2 space-y-10">

                    {{-- Resumen --}}
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Resumen del viaje
                        </h2>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Duración</p>
                                <p class="font-bold text-gray-900">{{ $booking->nights ? $booking->nights . ' Noches' : 'A confirmar' }}</p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pasajeros</p>
                                <p class="font-bold text-gray-900">{{ $booking->passengers ?? '-' }} personas</p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Estado</p>
                                <p class="font-bold text-gray-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[var(--color-secondary)]"></span>
                                    {{ $booking->status->getLabel() }}
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Ref. Interna</p>
                                <p class="font-bold text-gray-900">{{ $booking->file_number }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Servicios Incluidos --}}
                    @if($booking->items->isNotEmpty())
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Servicios Incluidos
                            </h2>
                            
                            <div class="space-y-4">
                                @foreach($booking->items as $item)
                                    @php
                                        $typeIcon = match(strtolower($item->serviceType?->name ?? '')) {
                                            'vuelo', 'aéreo', 'aereo', 'avión', 'avion' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
                                            'hotel', 'alojamiento', 'hospedaje'         => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                                            'traslado', 'transfer'                      => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                                            'seguro', 'asistencia'                      => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                                            default                                     => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                                        };
                                    @endphp
                                    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex gap-5 hover:shadow-md transition-shadow">
                                        <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center shrink-0 border border-gray-100">
                                            <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcon }}" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            @if($item->serviceType?->name)
                                                <p class="text-xs font-bold text-[var(--color-secondary)] uppercase tracking-widest mb-1">{{ $item->serviceType->name }}</p>
                                            @endif
                                            <h3 class="text-gray-900 font-semibold text-lg leading-snug">{{ $item->description }}</h3>
                                            @if($item->supplier?->name || $item->supplier_name)
                                                <p class="text-sm text-gray-500 mt-1">{{ $item->supplier?->name ?? $item->supplier_name }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-[var(--color-primary)] text-lg whitespace-nowrap">{{ $item->currency }} {{ number_format($item->sell, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        </div>
                    @endif

                    {{-- Itinerario Día a Día --}}
                    @if($booking->itineraryDays && $booking->itineraryDays->isNotEmpty())
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                Itinerario del Viaje
                            </h2>
                            
                            <div class="relative border-l-2 border-gray-200 ml-4 space-y-8 pb-4">
                                @foreach($booking->itineraryDays as $day)
                                    <div class="relative pl-8">
                                        {{-- Dot --}}
                                        <div class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full bg-[var(--color-secondary)] border-4 border-white shadow-sm"></div>
                                        
                                        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                            @if($day->image_path)
                                                <div class="h-48 w-full relative">
                                                    <img src="{{ Storage::url($day->image_path) }}" alt="{{ $day->title }}" class="absolute inset-0 w-full h-full object-cover">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                                    <div class="absolute bottom-4 left-4 text-white">
                                                        <span class="bg-[var(--color-primary)] text-xs font-bold px-2 py-1 rounded-md uppercase tracking-wide">Día {{ $day->day_number }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <div class="p-6">
                                                <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
                                                    <div>
                                                        @if(!$day->image_path)
                                                            <span class="inline-block bg-[var(--color-primary)]/10 text-[var(--color-primary)] text-xs font-bold px-2 py-1 rounded-md uppercase tracking-wide mb-2">Día {{ $day->day_number }}</span>
                                                        @endif
                                                        <h3 class="text-xl font-bold text-gray-900">{{ $day->title }}</h3>
                                                    </div>
                                                    @if($day->date || $day->location)
                                                        <div class="text-sm font-medium text-gray-500 text-right flex flex-col items-end gap-1">
                                                            @if($day->date)
                                                                <span class="flex items-center gap-1.5">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                                    {{ $day->date->format('d/m/Y') }}
                                                                </span>
                                                            @endif
                                                            @if($day->location)
                                                                <span class="flex items-center gap-1.5">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                                    {{ $day->location }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                @if($day->description)
                                                    <div class="prose prose-sm text-gray-600 max-w-none mt-2">
                                                        {!! nl2br(e($day->description)) !!}
                                                    </div>
                                                @endif
                                                
                                                @if(is_array($day->services) && count($day->services) > 0)
                                                    <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                        @foreach($day->services as $service)
                                                            @php
                                                                $srvIcon = match($service['type'] ?? '') {
                                                                    'flight' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
                                                                    'hotel' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                                                                    'transfer' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                                                                    'meal' => 'M21 15.546c-.523 1.159-1.523 1.995-2.727 2.246-1.58.33-3.142-.718-3.414-2.342a2.937 2.937 0 011.026-2.613l-4.512-4.512c-1.393.76-2.584 1.706-3.342 2.656-.991 1.242-1.288 2.37-1.109 3.036.216.804.887 1.341 1.637 1.547v2.241c-2.316-.395-4.14-2.352-4.46-4.832-.303-2.345 1.152-4.665 3.321-5.744l-4.71-4.71A1 1 0 013 3h2a1 1 0 011 1v4h2V4a1 1 0 011-1h2a1 1 0 01.707 1.707l2.879 2.879a8.03 8.03 0 015.632-1.365c1.474.225 2.766 1.01 3.593 2.155 1.109 1.536 1.157 3.633.189 5.17z',
                                                                    'tour' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                                                    default => 'M5 13l4 4L19 7'
                                                                };
                                                                $srvColor = match($service['type'] ?? '') {
                                                                    'flight' => 'text-blue-500 bg-blue-50',
                                                                    'hotel' => 'text-indigo-500 bg-indigo-50',
                                                                    'transfer' => 'text-teal-500 bg-teal-50',
                                                                    'meal' => 'text-orange-500 bg-orange-50',
                                                                    'tour' => 'text-purple-500 bg-purple-50',
                                                                    default => 'text-gray-500 bg-gray-50'
                                                                };
                                                            @endphp
                                                            <div class="flex items-center gap-2.5">
                                                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $srvColor }}">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $srvIcon }}" /></svg>
                                                                </div>
                                                                <span class="text-sm font-medium text-gray-700">{{ $service['description'] ?? 'Servicio' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notas Adicionales --}}
                    @if($booking->notes)
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Detalles de la Propuesta
                            </h2>
                            <div class="prose prose-lg text-gray-600 max-w-none bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                                {!! nl2br(e($booking->notes)) !!}
                            </div>
                        </div>
                    @endif

                    <p class="text-sm text-gray-400 text-center mt-12 pb-8 border-t border-gray-200 pt-8">
                        Propuesta preparada por {{ $settings?->company_name ?? config('app.name') }}.<br>
                        Esta cotización no implica reserva ni disponibilidad garantizada.
                    </p>
                </div>

                {{-- Sidebar (col-span-1) --}}
                <div class="md:col-span-1">
                    <div class="sticky top-8 space-y-6">
                        
                        {{-- Aviso de vencimiento --}}
                        @if($expired)
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3 text-red-800">
                                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div>
                                    <p class="font-bold">Propuesta Vencida</p>
                                    <p class="text-sm mt-1">Esta propuesta venció el {{ $booking->valid_until->format('d/m/Y') }}. Los valores podrían haber cambiado.</p>
                                </div>
                            </div>
                        @endif

                        {{-- Pricing Card --}}
                        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-6 text-white text-center" style="background: linear-gradient(135deg, var(--color-primary, #0f172a), var(--color-secondary, #3b82f6));">
                                <p class="text-sm font-medium text-white/80 uppercase tracking-widest mb-1">Inversión Total</p>
                                <p class="text-4xl lg:text-5xl font-extrabold flex justify-center items-start gap-1">
                                    <span class="text-xl lg:text-2xl mt-1 opacity-90">{{ $booking->currency }}</span>
                                    {{ number_format($booking->total_sell, 0, ',', '.') }}
                                </p>
                                @if($booking->valid_until && !$expired)
                                    <p class="text-sm text-white/80 mt-3 pt-3 border-t border-white/20">
                                        Válido hasta el {{ $booking->valid_until->format('d/m/Y') }}
                                    </p>
                                @endif
                            </div>

                            <div class="p-6 space-y-4">
                                @if(!$expired)
                                    @if($firstWhatsapp)
                                        @php
                                            $waText = urlencode("Hola! Vi la propuesta de viaje ({$booking->file_number}) y me interesa. ¿Podemos continuar?");
                                            $waUrl  = format_social_link($firstWhatsapp['url']) . "?text={$waText}";
                                        @endphp
                                        <a href="{{ $waUrl }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-4 px-6 bg-[#25D366] text-white text-center font-bold rounded-xl hover:bg-[#128C7E] transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.88-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            Escribinos para avanzar
                                        </a>
                                    @endif
                                @else
                                    @if($firstWhatsapp)
                                        @php
                                            $waText = urlencode("Hola! Me interesa renovar la propuesta de viaje ({$booking->file_number}) que recibí. ¿Pueden actualizarla?");
                                            $waUrl  = format_social_link($firstWhatsapp['url']) . "?text={$waText}";
                                        @endphp
                                        <a href="{{ $waUrl }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-4 px-6 bg-[#25D366] text-white text-center font-bold rounded-xl hover:bg-[#128C7E] transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.88-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            Pedir Actualización
                                        </a>
                                    @endif
                                @endif

                                <a href="{{ url()->current() }}?format=pdf" class="flex items-center justify-center gap-2 w-full py-3 px-6 bg-gray-50 text-gray-700 text-center font-bold rounded-xl border-2 border-gray-200 hover:bg-gray-100 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Descargar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
