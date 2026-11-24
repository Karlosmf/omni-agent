<?php

namespace App\Filament\Admin\Widgets;

use App\Services\SalesFunnelService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 22;

    protected function getStats(): array
    {
        $funnel = app(SalesFunnelService::class);
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        $thisMonth = $funnel->usdSales($currentMonth);
        $ventasActual = $thisMonth['sell'];
        $ventasAnterior = $funnel->usdSales($previousMonth, $currentMonth)['sell'];
        $ventasTrend = $ventasAnterior > 0 ? (($ventasActual - $ventasAnterior) / $ventasAnterior) * 100 : 0;
        $ventasIcon = $ventasTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $ventasColor = $ventasTrend >= 0 ? 'success' : 'danger';

        $rentabilidadActual = $ventasActual - $thisMonth['cost'];
        $historic = $funnel->usdSales();
        $rentabilidadHistorica = $historic['sell'] - $historic['cost'];

        $totals = $funnel->totals();
        $totalLeads = $totals['leads'];
        $totalConfirmados = $totals['sales'];
        $conversionRate = $totals['total_rate'];

        return [
            Stat::make('Ventas Mes Actual (USD)', 'USD '.number_format($ventasActual, 2))
                ->description(abs(round($ventasTrend, 1)).'% '.($ventasTrend >= 0 ? 'más' : 'menos').' que el mes pasado')
                ->descriptionIcon($ventasIcon)
                ->chart([$ventasAnterior, $ventasActual])
                ->color($ventasColor),

            Stat::make('Gross Profit del Mes (USD)', 'USD '.number_format($rentabilidadActual, 2))
                ->description('Beneficio neto histórico: USD '.number_format($rentabilidadHistorica, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Tasa de Conversión Histórica', $conversionRate.'%')
                ->description($totalConfirmados.' confirmados de '.$totalLeads.' contactos')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('warning'),
        ];
    }
}
