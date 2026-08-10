<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class SourcesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Orígenes de Consultas';

    protected function getData(): array
    {
        $sources = Lead::select('source', \DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $labels = $sources->map(fn ($s) => str_replace('_', ' ', strtoupper($s->source ?? 'Desconocido')))->toArray();
        $data = $sources->map(fn ($s) => $s->total)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Leads por Origen',
                    'data' => $data,
                    'backgroundColor' => [
                        '#f59e0b', // amber-500
                        '#10b981', // emerald-500
                        '#3b82f6', // blue-500
                        '#6366f1', // indigo-500
                        '#ec4899', // pink-500
                        '#6b7280', // gray-500
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
