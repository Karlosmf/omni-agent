<div style="display:flex; flex-direction:column; gap:1rem; padding-top:0.5rem;">
    {{-- URL field con botón de copiar --}}
    <div
        x-data="{ copied: false }"
        style="display:flex; align-items:center; gap:0.5rem;"
    >
        <x-filament::input.wrapper style="flex:1;">
            <x-filament::input
                type="text"
                readonly
                value="{{ $url }}"
                id="booking-public-url"
                @click="$el.select()"
            />
        </x-filament::input.wrapper>

        <x-filament::button
            color="gray"
            @click="
                navigator.clipboard.writeText('{{ $url }}')
                    .then(() => { copied = true; setTimeout(() => copied = false, 2000); })
                    .catch(() => document.getElementById('booking-public-url').select())
            "
        >
            <span x-show="!copied">Copiar</span>
            <span x-show="copied" style="display:none; color: green;">Copiado!</span>
        </x-filament::button>
    </div>

    {{-- Info chips --}}
    <div style="display:flex; flex-wrap:wrap; gap:0.75rem; font-size:0.875rem; color:gray;">
        <span style="display:flex; align-items:center; gap:0.25rem;">
            <x-heroicon-o-hashtag style="width:1rem; height:1rem;" />
            {{ $booking->file_number }}
        </span>
        @if($booking->destination)
            <span style="display:flex; align-items:center; gap:0.25rem;">
                <x-heroicon-o-map-pin style="width:1rem; height:1rem;" />
                {{ $booking->destination }}
            </span>
        @endif
        <span style="display:flex; align-items:center; gap:0.25rem;">
            <x-heroicon-o-currency-dollar style="width:1rem; height:1rem;" />
            {{ $booking->currency }} {{ number_format($booking->total_sell, 0, ',', '.') }}
        </span>
    </div>

    {{-- Preview link --}}
    <a
        href="{{ $url }}"
        target="_blank"
        style="display:flex; align-items:center; gap:0.25rem; font-size:0.875rem; text-decoration:underline; margin-top:0.5rem;"
    >
        <x-heroicon-o-arrow-top-right-on-square style="width:1rem; height:1rem;" />
        Previsualizar como el cliente lo verá
    </a>
</div>
