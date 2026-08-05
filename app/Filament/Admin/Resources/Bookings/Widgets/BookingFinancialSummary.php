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

        $currency = $booking->currency ?? 'USD';

        $totalVenta = (float) $booking->total_sell;

        $totalPagado = $booking->transactions()
            ->where('type', 'cobro')
            ->sum('amount_usd_fixed'); // Or calculate equivalent based on currency

        // Simplified: use amount if currency matches
        $totalPagadoCalc = $booking->transactions()
            ->where('type', 'cobro')
            ->where('currency', $currency)
            ->sum('amount');

        // If there are other currencies, we fallback to usd fixed if booking is USD
        if ($currency === 'USD') {
            $totalPagadoCalc = $booking->transactions()
                ->where('type', 'cobro')
                ->sum('amount_usd_fixed');
        }

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
