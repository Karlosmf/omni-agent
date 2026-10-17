<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    @php
        $selected = $this->selectedLead();
        $conversations = $this->conversations();
        $templates = \App\Models\WhatsAppTemplate::where('is_active', true)->get();
        $chosenTemplate = $templates->firstWhere('id', $this->templateId);
    @endphp

    <div wire:poll.5s class="omni-stack">
        @unless ($this->isConfigured())
            <x-filament::section>
                <p class="omni-text-sm">WhatsApp todavía no está conectado. Completá <code>WHATSAPP_PHONE_NUMBER_ID</code>, <code>WHATSAPP_ACCESS_TOKEN</code>, <code>WHATSAPP_APP_SECRET</code> y <code>WHATSAPP_VERIFY_TOKEN</code> en el <code>.env</code> y registrá este webhook en Meta: <code>{{ url('/webhooks/whatsapp') }}</code></p>
            </x-filament::section>
        @endunless

        <div class="omni-layout">
            {{-- Conversations --}}
            <x-filament::section>
                <div class="omni-pills">
                    @foreach (['all' => 'Todas', 'unread' => 'No leídas', 'mine' => 'Mías', 'hot' => 'Urgentes'] as $key => $label)
                        <button wire:click="$set('filter', '{{ $key }}')" type="button" class="omni-pill {{ $this->filter === $key ? 'is-active' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <ul class="omni-list">
                    @forelse ($conversations as $conversation)
                        <li>
                            <button wire:click="open({{ $conversation->id }})" type="button" class="omni-conv {{ $this->lead === $conversation->id ? 'is-selected' : '' }}">
                                <span style="min-width: 0;">
                                    <span class="omni-ellipsis omni-strong omni-text-sm">{{ $conversation->customer_name ?: $conversation->customer?->name ?: 'Sin nombre' }}</span>
                                    <span class="omni-ellipsis omni-muted">{{ $conversation->customer?->phone }}</span>
                                </span>
                                <span class="omni-conv-side">
                                    @if ($conversation->unread_count > 0)
                                        <x-filament::badge color="success">{{ $conversation->unread_count }}</x-filament::badge>
                                    @endif
                                    @if ($conversation->ai_paused)
                                        <x-filament::badge color="gray">IA en pausa</x-filament::badge>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @empty
                        <li><p class="omni-muted" style="padding: 1.5rem 0; text-align: center;">Todavía no hay conversaciones.</p></li>
                    @endforelse
                </ul>
            </x-filament::section>

            {{-- Chat --}}
            <x-filament::section>
                @if ($selected)
                    <div class="omni-between" style="margin-bottom: 1rem;">
                        <div>
                            <p class="omni-strong" style="margin: 0;">{{ $selected->customer_name ?: $selected->customer?->name }}</p>
                            <p class="omni-muted" style="margin: 0;">{{ $selected->customer?->phone }} · {{ $selected->isInsideWhatsAppWindow() ? 'Ventana de 24 h abierta' : 'Fuera de la ventana de 24 h: solo plantillas' }}</p>
                        </div>
                        <div class="omni-row">
                            <x-filament::button size="sm" color="gray" wire:click="toggleAi">{{ $selected->ai_paused ? 'Reanudar IA' : 'Pausar IA' }}</x-filament::button>
                            <x-filament::button size="sm" color="gray" tag="a" :href="$this->leadUrl($selected)">Ver lead</x-filament::button>
                        </div>
                    </div>

                    <div class="omni-thread">
                        @foreach ($selected->messages as $message)
                            <div class="omni-msg {{ $message->direction === 'in' ? '' : 'is-out' }}">
                                <div class="omni-bubble">
                                    <p>{{ $message->content }}</p>
                                    <small>
                                        {{ $message->created_at->format('d/m H:i') }}
                                        @if ($message->direction === 'out') · {{ $message->sent_by_user_id ? 'Equipo' : 'IA' }} · {{ $message->status }} @endif
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form wire:submit="send" class="omni-form">
                        @if ($selected->isInsideWhatsAppWindow())
                            <textarea wire:model="reply" rows="3" placeholder="Escribí tu respuesta… (al responder vos, la IA se pausa)" class="omni-input"></textarea>
                        @else
                            <select wire:model.live="templateId" class="omni-input">
                                <option value="">Elegí una plantilla aprobada…</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->purpose }} — {{ $template->name }}</option>
                                @endforeach
                            </select>
                            @if ($chosenTemplate)
                                <p class="omni-note" style="margin: 0;">{{ $chosenTemplate->body }}</p>
                                @for ($i = 0; $i < $chosenTemplate->variables_count; $i++)
                                    <input type="text" wire:model="templateVariables.{{ $i }}" placeholder="Valor de @{{ {{ $i + 1 }} }}" class="omni-input">
                                @endfor
                            @endif
                        @endif

                        <div>
                            <x-filament::button type="submit" icon="heroicon-o-paper-airplane" :disabled="! $this->isConfigured()">Enviar</x-filament::button>
                        </div>
                    </form>
                @else
                    <p class="omni-muted" style="padding: 4rem 0; text-align: center;">Elegí una conversación para verla.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
