<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $confirmedStatuses = [BookingStatus::Senado, BookingStatus::Emitido];

        // Current Month
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        // Ventas USD (Mes actual)
        $ventasActual = Booking::whereIn('status', $confirmedStatuses)
            ->where('currency', 'USD')
            ->where('created_at', '>=', $currentMonth)
            ->sum('total_sell');

        $ventasAnterior = Booking::whereIn('status', $confirmedStatuses)
            ->where('currency', 'USD')
            ->where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->sum('total_sell');

        $ventasTrend = $ventasAnterior > 0 ? (($ventasActual - $ventasAnterior) / $ventasAnterior) * 100 : 0;
        $ventasIcon = $ventasTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $ventasColor = $ventasTrend >= 0 ? 'success' : 'danger';

        // Rentabilidad USD (Mes actual)
        $costosActual = Booking::whereIn('status', $confirmedStatuses)
            ->where('currency', 'USD')
            ->where('created_at', '>=', $currentMonth)
            ->sum('total_cost');

        $rentabilidadActual = $ventasActual - $costosActual;

        // Rentabilidad histórica (USD)
        $rentabilidadHistorica = Booking::whereIn('status', $confirmedStatuses)
            ->where('currency', 'USD')
            ->sum(\DB::raw('total_sell - total_cost'));

        // Tasa de conversión (General)
        $totalLeads = Lead::count();
        $totalConfirmados = Booking::whereIn('status', $confirmedStatuses)->count();
        $conversionRate = $totalLeads > 0 ? round(($totalConfirmados / $totalLeads) * 100, 1) : 0;

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
