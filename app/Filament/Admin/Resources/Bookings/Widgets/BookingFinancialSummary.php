<?php

namespace App\Filament\Admin\Resources\Bookings\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class BookingFinancialSummary extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        /** @var Booking $booking */
        $booking = $this->record;

        if (! $booking) {
            return [];
        }

        $currency = 'USD';

        $totalVenta = (float) $booking->total_sell;

        // Every cobro carries its USD equivalent at the rate of the day it was paid.
        $totalPagadoCalc = (float) $booking->transactions()
            ->where('type', 'cobro')
            ->sum('amount_usd_fixed');

        $saldoAdeudado = $totalVenta - $totalPagadoCalc;

        $saldoColor = $saldoAdeudado <= 0 ? 'success' : 'danger';
        $saldoIcon = $saldoAdeudado <= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle';

        return [
            Stat::make('Total del Viaje', $currency.' '.number_format($totalVenta, 2))
                ->description('Monto total de venta')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Total Pagado', $currency.' '.number_format($totalPagadoCalc, 2))
                ->description('Cobros registrados')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Saldo Adeudado', $currency.' '.number_format($saldoAdeudado, 2))
                ->description($saldoAdeudado <= 0 ? 'Viaje saldado' : 'Pendiente de cobro')
                ->descriptionIcon($saldoIcon)
                ->color($saldoColor),
        ];
    }
}
