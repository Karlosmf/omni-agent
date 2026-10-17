<x-filament-widgets::widget>
    @include('filament.admin.partials.omni-styles')

    <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
        <x-slot name="heading">El cron no está corriendo</x-slot>

        <p class="omni-text-sm" style="margin: 0;">
            Los mails, las respuestas de IA y los recordatorios quedan en espera hasta que se configure una tarea programada
            cada minuto en el panel de tu hosting.
            @if ($lastBeat)
                Último latido: {{ $lastBeat }}.
            @else
                Todavía no se registró ningún latido.
            @endif
        </p>

        <p class="omni-text-sm" style="margin: .75rem 0 0;">Creá esta tarea (cambiá la ruta de PHP por la de tu hosting):</p>
        <code class="omni-code">{{ $command }}</code>
    </x-filament::section>
</x-filament-widgets::widget>
