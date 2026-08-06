<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LeadSourcesChart extends ChartWidget
{
    protected ?string $heading = 'Fuentes de Leads';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $sources = Lead::select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get();
            
        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $sources->pluck('count')->toArray(),
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6'],
                ],
            ],
            'labels' => $sources->pluck('source')->map(fn($source) => match($source) {
                'chatbot' => 'Chatbot IA',
                'web_form' => 'Formulario Web / Catálogo',
                'whatsapp' => 'WhatsApp Directo',
                'manual' => 'Manual (Agente)',
                default => ucfirst($source ?? 'Desconocido'),
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
