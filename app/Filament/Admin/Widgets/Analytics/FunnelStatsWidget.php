<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FunnelStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalLeads = Lead::count();
        $totalCotizados = Booking::count();
        $totalConfirmados = Booking::whereIn('status', [
            BookingStatus::Senado,
            BookingStatus::Emitido,
        ])->count();

        $cotizacionRate = $totalLeads > 0 ? round(($totalCotizados / $totalLeads) * 100, 1) : 0;
        $confirmacionRate = $totalCotizados > 0 ? round(($totalConfirmados / $totalCotizados) * 100, 1) : 0;
        $totalRate = $totalLeads > 0 ? round(($totalConfirmados / $totalLeads) * 100, 1) : 0;

        return [
            Stat::make('Leads Capturados', $totalLeads)
                ->description("Conversión a cotización: {$cotizacionRate}%")
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('gray'),

            Stat::make('Cotizaciones Enviadas', $totalCotizados)
                ->description("Conversión a venta: {$confirmacionRate}%")
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('warning'),

            Stat::make('Viajes Vendidos', $totalConfirmados)
                ->description("Conversión total: {$totalRate}%")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
