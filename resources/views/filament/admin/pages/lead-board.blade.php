<x-filament-panels::page>
    @include('filament.admin.partials.omni-styles')

    <style>
        .omni-board { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(16rem, 1fr); gap: 1rem; overflow-x: auto; padding-bottom: .5rem; align-items: start; }
        .omni-col { border-radius: .75rem; background: var(--gray-50); padding: .75rem; min-height: 12rem; transition: box-shadow .15s; }
        .dark .omni-col { background: rgb(255 255 255 / .04); }
        .omni-col.is-over { box-shadow: inset 0 0 0 2px var(--primary-500); }
        .omni-col-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
        .omni-col-cards { display: grid; gap: .5rem; }
        .omni-card { display: block; border-radius: .5rem; background: #fff; padding: .6rem .75rem; box-shadow: 0 1px 2px rgb(0 0 0 / .08); cursor: grab; color: inherit; text-decoration: none; border-left: 3px solid transparent; }
        .dark .omni-card { background: var(--gray-800); }
        .omni-card.is-hot { border-left-color: var(--danger-500); }
        .omni-card.is-warm { border-left-color: var(--warning-500); }
        .omni-card.is-dragging { opacity: .4; }
        .omni-card-meta { display: flex; flex-wrap: wrap; gap: .35rem .75rem; margin-top: .35rem; font-size: .7rem; color: var(--gray-500); }
        .omni-board-hint { margin: 0 0 1rem; }
    </style>

    @php($columns = $this->columns())

    <div class="omni-row omni-board-hint">
        <label class="omni-row omni-text-sm"><input type="checkbox" wire:model.live="onlyMine"> Solo mis consultas</label>
        <label class="omni-row omni-text-sm"><input type="checkbox" wire:model.live="onlyHot"> Solo calientes</label>
        <label class="omni-row omni-text-sm"><input type="checkbox" wire:model.live="showClosed"> Mostrar cerradas</label>
        <span class="omni-muted">Arrastrá una tarjeta a otra columna para cambiar su estado. En cada columna, lo más urgente va arriba.</span>
    </div>

    <div class="omni-board" x-data="{ dragging: null, over: null }">
        @foreach ($columns as $column)
            @php($status = $column['status'])
            @php($droppable = in_array($status, \App\Filament\Admin\Pages\LeadBoard::DROPPABLE, true))
            <section
                wire:key="col-{{ $status->value }}"
                class="omni-col"
                :class="{ 'is-over': over === '{{ $status->value }}' }"
                @if ($droppable)
                    x-on:dragover.prevent="over = '{{ $status->value }}'"
                    x-on:dragleave="over = null"
                    x-on:drop.prevent="over = null; if (dragging) { $wire.moveLead(dragging, '{{ $status->value }}') }"
                @endif
                aria-label="{{ $status->getLabel() }}"
            >
                <div class="omni-col-head">
                    <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                    <span class="omni-muted">{{ $column['total'] }}</span>
                </div>

                <div class="omni-col-cards">
                    @forelse ($column['leads'] as $lead)
                        @php($overdue = $lead->isFollowUpOverdue())
                        <a
                            wire:key="lead-{{ $lead->id }}"
                            href="{{ $this->editUrl($lead) }}"
                            class="omni-card {{ $lead->temperature === \App\Enums\LeadTemperature::Hot ? 'is-hot' : ($lead->temperature === \App\Enums\LeadTemperature::Warm ? 'is-warm' : '') }}"
                            draggable="true"
                            x-on:dragstart="dragging = {{ $lead->id }}; $el.classList.add('is-dragging')"
                            x-on:dragend="dragging = null; $el.classList.remove('is-dragging')"
                        >
                            <div class="omni-between">
                                <span class="omni-strong omni-text-sm omni-ellipsis">{{ $lead->displayName() }}</span>
                                @if ($lead->needs_human_attention)
                                    <x-filament::badge color="danger" size="sm">Atención</x-filament::badge>
                                @endif
                            </div>
                            @if ($lead->destination())
                                <div class="omni-muted omni-ellipsis">✈️ {{ $lead->destination() }}</div>
                            @endif
                            <div class="omni-card-meta">
                                @if ($lead->temperature)
                                    <span>{{ $lead->temperature->getLabel() }}</span>
                                @endif
                                @if ($lead->next_follow_up_at)
                                    <span class="{{ $overdue ? 'omni-danger omni-strong' : '' }}">Seguimiento {{ $lead->next_follow_up_at->format('d/m') }}</span>
                                @endif
                                <span>{{ $lead->last_contacted_at ? 'Contacto '.$lead->last_contacted_at->diffForHumans() : 'Sin contactar' }}</span>
                                @if ($lead->agent)
                                    <span>{{ $lead->agent->name }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="omni-muted">Sin consultas.</p>
                    @endforelse

                    @if ($column['total'] > $column['leads']->count())
                        <p class="omni-muted">Y {{ $column['total'] - $column['leads']->count() }} más en la lista.</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
