<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    <div class="omni-stack">
        <x-filament::section>
            <p class="omni-text-sm" style="margin: 0 0 1rem;">
                Convierte un PDF en texto para pegarlo en <strong>Importar idea de viaje → Texto pegado</strong>. Sirve cuando usás un proveedor de IA que no lee PDFs (como Groq). Funciona con PDFs de texto; si el PDF es una imagen escaneada no hay texto para leer.
            </p>

            <form wire:submit="convert" class="omni-row">
                <input type="file" wire:model="file" accept="application/pdf" class="omni-input" style="max-width: 24rem;">
                <x-filament::button type="submit" icon="heroicon-o-arrow-path" wire:loading.attr="disabled" wire:target="file,convert">Convertir</x-filament::button>
                @if ($text !== '')
                    <x-filament::button color="gray" wire:click="clear" type="button">Limpiar</x-filament::button>
                @endif
            </form>

            @error('file') <p class="omni-danger omni-text-sm" style="margin: .5rem 0 0;">{{ $message }}</p> @enderror
            <p wire:loading wire:target="file,convert" class="omni-muted" style="margin: .5rem 0 0;">Procesando…</p>
        </x-filament::section>

        @if ($text !== '')
            <x-filament::section>
                <div class="omni-between" style="margin-bottom: .75rem;">
                    <p class="omni-muted" style="margin: 0;">{{ number_format(mb_strlen($text), 0, ',', '.') }} caracteres</p>
                    <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" wire:click="download" type="button">Descargar .txt</x-filament::button>
                </div>
                <textarea readonly rows="18" class="omni-input" onclick="this.select()">{{ $text }}</textarea>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
