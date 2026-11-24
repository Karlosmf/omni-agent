<?php

namespace App\Filament\Admin\Widgets;

use App\Services\SalesFunnelService;
use Filament\Widgets\ChartWidget;

class FunnelChart extends ChartWidget
{
    protected ?string $heading = 'Funnel de Conversión';

    protected static ?int $sort = 13;

    protected function getData(): array
    {
        $funnel = app(SalesFunnelService::class)->totals();

        return [
            'datasets' => [
                [
                    'label' => 'Conversión',
                    'data' => [$funnel['leads'], $funnel['quotes'], $funnel['sales']],
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981'],
                ],
            ],
            'labels' => ['Leads', 'Presupuestos Creados', 'Reservas Confirmadas'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
