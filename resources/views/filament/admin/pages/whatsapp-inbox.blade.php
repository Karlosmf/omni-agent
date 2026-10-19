<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    @php
        $selected = $this->selectedLead();
        $conversations = $this->conversations();
        $accounts = $this->accounts();
        $templates = \App\Models\WhatsAppTemplate::where('is_active', true)->get();
        $chosenTemplate = $templates->firstWhere('id', $this->templateId);
    @endphp

    <div wire:poll.5s class="omni-stack">
        @unless ($this->isConfigured())
            <x-filament::section>
                <p class="omni-text-sm">Todavía no hay canales conectados. Conectá tus números y cuentas en <strong>Ajustes → Canales de mensajería</strong>. Webhooks para registrar en Meta: WhatsApp <code>{{ url('/webhooks/whatsapp') }}</code> · Instagram y Messenger <code>{{ url('/webhooks/meta') }}</code></p>
            </x-filament::section>
        @endunless

        <div class="omni-layout">
            {{-- Conversations --}}
            <x-filament::section>
                <div class="omni-pills" style="margin-bottom: .5rem;">
                    <button wire:click="setChannel('all')" type="button" class="omni-pill {{ $this->channel === 'all' ? 'is-active' : '' }}">Todos los canales</button>
                    @foreach (\App\Enums\Channel::inbox() as $channelOption)
                        <button wire:click="setChannel('{{ $channelOption->value }}')" type="button" class="omni-pill {{ $this->channel === $channelOption->value ? 'is-active' : '' }}">{{ $channelOption->getLabel() }}</button>
                    @endforeach
                </div>

                @if ($accounts->count() > 1)
                    <select wire:model.live="account" class="omni-input" style="margin-bottom: .5rem;">
                        <option value="">Todas las cuentas y números</option>
                        @foreach ($accounts as $accountOption)
                            <option value="{{ $accountOption->id }}">{{ $accountOption->channel->getLabel() }} · {{ $accountOption->label }}</option>
                        @endforeach
                    </select>
                @endif

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
                                    <span class="omni-ellipsis omni-muted">{{ $conversation->conversationChannel()->getLabel() }}@if ($conversation->channelAccount) · {{ $conversation->channelAccount->label }}@endif @if ($conversation->customer?->phone) · {{ $conversation->customer->phone }}@endif</span>
                                </span>
                                <span class="omni-conv-side">
                                    @if ($conversation->unread_count > 0)
                                        <x-filament::badge color="success">{{ $conversation->unread_count }}</x-filament::badge>
                                    @endif
                                    @if ($conversation->needs_human_attention && ! $conversation->first_response_at)
                                        <x-filament::badge color="danger">Esperando</x-filament::badge>
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
                            <p class="omni-muted" style="margin: 0;">{{ $selected->conversationChannel()->getLabel() }}@if ($selected->channelAccount) ({{ $selected->channelAccount->label }})@endif @if ($selected->customer?->phone) · {{ $selected->customer->phone }}@endif · {{ $selected->isInsideReplyWindow() ? 'Ventana de 24 h abierta' : ($selected->conversationChannel()->isSocial() ? 'Fuera de la ventana de 24 h: no se puede responder' : 'Fuera de la ventana de 24 h: solo plantillas') }}</p>
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
                        @if ($selected->isInsideReplyWindow())
                            <textarea wire:model="reply" rows="3" placeholder="Escribí tu respuesta… (al responder vos, la IA se pausa)" class="omni-input"></textarea>
                        @elseif ($selected->conversationChannel()->isSocial())
                            <p class="omni-note" style="margin: 0;">Pasaron más de 24 horas desde el último mensaje del cliente. Cuando vuelva a escribir podés responderle desde acá.</p>
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
                            <x-filament::button type="submit" icon="heroicon-o-paper-airplane" :disabled="! $this->isConfigured($selected)">Enviar</x-filament::button>
                        </div>
                    </form>
                @else
                    <p class="omni-muted" style="padding: 4rem 0; text-align: center;">Elegí una conversación para verla.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
