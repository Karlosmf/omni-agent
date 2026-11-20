<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    <div class="omni-stack">
        @if ($isTesting)
            <x-filament::section>
                <x-slot name="heading">Chat de prueba</x-slot>
                <x-slot name="description">Usa lo guardado. No se le envía nada a nadie, no se guarda y no genera avisos, pero sí consume la IA.</x-slot>
                <x-slot name="afterHeader">
                    <x-filament::button size="sm" color="gray" wire:click="resetTest">Empezar de nuevo</x-filament::button>
                </x-slot>

                <div class="omni-layout">
                    <dl class="omni-dl">
                        @foreach ($this->testSummary() as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div>
                        <div class="omni-thread">
                            @forelse ($testChat as $message)
                                <div class="omni-msg {{ $message['role'] === 'user' ? '' : 'is-out' }}">
                                    <div class="omni-bubble">
                                        <p>{{ $message['content'] }}</p>
                                        @if (! empty($message['buttons']))
                                            <div class="omni-chips">
                                                @foreach ($message['buttons'] as $button)
                                                    <span class="omni-chip">{{ $button }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if (filled($message['handoff'] ?? null))
                                            <small>Aviso al equipo: {{ $message['handoff'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="omni-muted">Escribí como lo haría un cliente.</p>
                            @endforelse
                        </div>

                        <form wire:submit="sendTest" class="omni-form">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="testMessage" placeholder="Hola, quiero viajar a Punta Cana" aria-label="Mensaje de prueba" />
                            </x-filament::input.wrapper>
                            <div>
                                <x-filament::button type="submit" wire:target="sendTest" wire:loading.attr="disabled">Enviar</x-filament::button>
                            </div>
                        </form>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <form wire:submit="save">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page>
