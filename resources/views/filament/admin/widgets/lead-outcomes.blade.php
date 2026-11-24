<x-filament-widgets::widget>
    @include('filament.admin.partials.omni-styles')

    <style>
        .lo-cards { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 1rem; }
        @media (min-width: 1024px) { .lo-cards { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .lo-card { display: block; border: 1px solid var(--gray-200); border-radius: .75rem; padding: .75rem 1rem; color: inherit; text-decoration: none; }
        .dark .lo-card { border-color: rgb(255 255 255 / .1); }
        a.lo-card:hover { border-color: var(--primary-500); }
        .lo-value { font-size: 1.75rem; font-weight: 600; line-height: 1.2; }
        .lo-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .lo-table th, .lo-table td { padding: .4rem .5rem; text-align: right; border-bottom: 1px solid var(--gray-200); }
        .dark .lo-table th, .dark .lo-table td { border-color: rgb(255 255 255 / .1); }
        .lo-table th:first-child, .lo-table td:first-child { text-align: left; }
        .lo-table th { font-weight: 600; color: var(--gray-500); font-size: .75rem; }
        .lo-table a { color: var(--primary-600); text-decoration: underline; }
    </style>

    <x-filament::section icon="heroicon-o-funnel">
        <x-slot name="heading">Seguimiento de consultas</x-slot>
        <x-slot name="description">
            Consultas recibidas del {{ $stats->from->format('d/m/Y') }} al {{ $stats->until->format('d/m/Y') }} y cómo terminaron.
        </x-slot>

        <div class="omni-row">
            <div class="omni-pills" style="margin: 0;">
                @foreach ($periods as $key => $label)
                    <button type="button" wire:click="$set('period', '{{ $key }}')" @class(['omni-pill', 'is-active' => $period === $key])>{{ $label }}</button>
                @endforeach
            </div>

            @if ($period === 'custom')
                <div class="omni-row">
                    <label class="omni-text-sm">Desde <input type="date" class="omni-input" style="width: auto;" wire:model.live="from"></label>
                    <label class="omni-text-sm">Hasta <input type="date" class="omni-input" style="width: auto;" wire:model.live="until"></label>
                </div>
            @endif
        </div>

        <div class="lo-cards">
            <a class="lo-card" href="{{ $this->listUrl($stats, 'todas') }}">
                <div class="omni-muted">Recibidas</div>
                <div class="lo-value">{{ $totals['received'] }}</div>
            </a>
            <a class="lo-card" href="{{ $this->listUrl($stats, 'ganadas') }}">
                <div class="omni-muted">Concretadas</div>
                <div class="lo-value" style="color: var(--success-600);">{{ $totals['won'] }}</div>
            </a>
            <a class="lo-card" href="{{ $this->listUrl($stats, 'perdidas') }}">
                <div class="omni-muted">Perdidas</div>
                <div class="lo-value" style="color: var(--danger-600);">{{ $totals['lost'] }}</div>
            </a>
            <a class="lo-card" href="{{ $this->listUrl($stats, 'abiertas') }}">
                <div class="omni-muted">Abiertas (a seguir)</div>
                <div class="lo-value" style="color: var(--warning-600);">{{ $totals['open'] }}</div>
            </a>
            <div class="lo-card">
                <div class="omni-muted">Conversión</div>
                <div class="lo-value">{{ number_format($totals['conversion'], 1, ',', '.') }}%</div>
            </div>
        </div>

        @if ($totals['received'] > 0)
            <div class="omni-two" style="margin-top: 1.5rem;">
                @foreach ([['Por origen', $bySource, 'source'], ['Por vendedor', $byAgent, 'agent_id']] as [$title, $rows, $filter])
                    <div>
                        <p class="omni-strong omni-text-sm" style="margin: 0 0 .5rem;">{{ $title }}</p>
                        <table class="lo-table">
                            <thead>
                                <tr><th></th><th>Recibidas</th><th>Concretadas</th><th>Perdidas</th><th>Abiertas</th><th>Conv.</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php($rowFilters = $row[$filter] === null ? null : [$filter => ['value' => $row[$filter]]])
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ $row['received'] }}</td>
                                        <td>{{ $row['won'] }}</td>
                                        <td>
                                            @if ($rowFilters && $row['lost'] > 0)
                                                <a href="{{ $this->listUrl($stats, 'perdidas', $rowFilters) }}">{{ $row['lost'] }}</a>
                                            @else
                                                {{ $row['lost'] }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rowFilters && $row['open'] > 0)
                                                <a href="{{ $this->listUrl($stats, 'abiertas', $rowFilters) }}">{{ $row['open'] }}</a>
                                            @else
                                                {{ $row['open'] }}
                                            @endif
                                        </td>
                                        <td>{{ number_format($row['conversion'], 1, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
