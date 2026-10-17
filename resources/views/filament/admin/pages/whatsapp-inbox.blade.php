<x-filament-panels::page>
    @php
        $selected = $this->selectedLead();
        $conversations = $this->conversations();
        $templates = \App\Models\WhatsAppTemplate::where('is_active', true)->get();
        $chosenTemplate = $templates->firstWhere('id', $this->templateId);
    @endphp

    <div wire:poll.5s class="space-y-4">
        @unless ($this->isConfigured())
            <x-filament::section>
                <p class="text-sm">WhatsApp todavía no está conectado. Completá <code>WHATSAPP_PHONE_NUMBER_ID</code>, <code>WHATSAPP_ACCESS_TOKEN</code>, <code>WHATSAPP_APP_SECRET</code> y <code>WHATSAPP_VERIFY_TOKEN</code> en el <code>.env</code> y registrá este webhook en Meta: <code>{{ url('/webhooks/whatsapp') }}</code></p>
            </x-filament::section>
        @endunless

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Conversations --}}
            <x-filament::section class="lg:col-span-1">
                <div class="mb-3 flex flex-wrap gap-2 text-xs">
                    @foreach (['all' => 'Todas', 'unread' => 'No leídas', 'mine' => 'Mías', 'hot' => 'Urgentes'] as $key => $label)
                        <button wire:click="$set('filter', '{{ $key }}')" type="button"
                            class="rounded-full border px-3 py-1 {{ $this->filter === $key ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <ul class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($conversations as $conversation)
                        <li>
                            <button wire:click="open({{ $conversation->id }})" type="button"
                                class="flex w-full items-center justify-between gap-2 px-2 py-3 text-left {{ $this->lead === $conversation->id ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ $conversation->customer_name ?: $conversation->customer?->name ?: 'Sin nombre' }}</span>
                                    <span class="block truncate text-xs text-gray-500">{{ $conversation->customer?->phone }}</span>
                                </span>
                                <span class="flex shrink-0 flex-col items-end gap-1">
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
                        <li class="py-6 text-center text-sm text-gray-500">Todavía no hay conversaciones.</li>
                    @endforelse
                </ul>
            </x-filament::section>

            {{-- Chat --}}
            <x-filament::section class="lg:col-span-2">
                @if ($selected)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $selected->customer_name ?: $selected->customer?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $selected->customer?->phone }} · {{ $selected->isInsideWhatsAppWindow() ? 'Ventana de 24 h abierta' : 'Fuera de la ventana de 24 h: solo plantillas' }}</p>
                        </div>
                        <div class="flex gap-2">
                            <x-filament::button size="sm" color="gray" wire:click="toggleAi">{{ $selected->ai_paused ? 'Reanudar IA' : 'Pausar IA' }}</x-filament::button>
                            <x-filament::button size="sm" color="gray" tag="a" :href="$this->leadUrl($selected)">Ver lead</x-filament::button>
                        </div>
                    </div>

                    <div class="max-h-[28rem] space-y-2 overflow-y-auto rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        @foreach ($selected->messages as $message)
                            <div class="flex {{ $message->direction === 'in' ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[80%] rounded-xl px-3 py-2 text-sm {{ $message->direction === 'in' ? 'bg-white shadow-sm dark:bg-gray-800' : 'bg-primary-100 text-primary-900' }}">
                                    <p class="whitespace-pre-line">{{ $message->content }}</p>
                                    <p class="mt-1 text-[10px] text-gray-500">
                                        {{ $message->created_at->format('d/m H:i') }}
                                        @if ($message->direction === 'out') · {{ $message->sent_by_user_id ? 'Equipo' : 'IA' }} · {{ $message->status }} @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form wire:submit="send" class="mt-4 space-y-3">
                        @if ($selected->isInsideWhatsAppWindow())
                            <textarea wire:model="reply" rows="3" placeholder="Escribí tu respuesta… (al responder vos, la IA se pausa)"
                                class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
                        @else
                            <select wire:model.live="templateId" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                                <option value="">Elegí una plantilla aprobada…</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->purpose }} — {{ $template->name }}</option>
                                @endforeach
                            </select>
                            @if ($chosenTemplate)
                                <p class="rounded bg-gray-50 p-2 text-xs dark:bg-white/5">{{ $chosenTemplate->body }}</p>
                                @for ($i = 0; $i < $chosenTemplate->variables_count; $i++)
                                    <input type="text" wire:model="templateVariables.{{ $i }}" placeholder="Valor de @{{ {{ $i + 1 }} }}"
                                        class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                                @endfor
                            @endif
                        @endif

                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane" :disabled="! $this->isConfigured()">Enviar</x-filament::button>
                    </form>
                @else
                    <p class="py-16 text-center text-sm text-gray-500">Elegí una conversación para verla.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
