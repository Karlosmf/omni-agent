<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Services\SalesFunnelService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FunnelStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $funnel = app(SalesFunnelService::class)->totals();

        return [
            Stat::make('Leads Capturados', $funnel['leads'])
                ->description("Conversión a cotización: {$funnel['quote_rate']}%")
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('gray'),

            Stat::make('Cotizaciones Enviadas', $funnel['quotes'])
                ->description("Conversión a venta: {$funnel['sale_rate']}%")
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('warning'),

            Stat::make('Viajes Vendidos', $funnel['sales'])
                ->description("Conversión total: {$funnel['total_rate']}%")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
