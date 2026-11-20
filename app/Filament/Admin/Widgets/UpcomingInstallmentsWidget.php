<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PaymentInstallment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UpcomingInstallmentsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 21;

    protected ?string $heading = 'Cobranzas';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_finances', 'manage_transactions', 'manage_bookings']) ?? false;
    }

    protected function getStats(): array
    {
        $open = PaymentInstallment::whereIn('status', ['pending', 'partial', 'overdue']);

        $overdue = (clone $open)->whereDate('due_date', '<', today());
        $upcoming = (clone $open)->whereDate('due_date', '>=', today())->whereDate('due_date', '<=', today()->addDays(7));

        $outstanding = fn ($query) => (float) $query->sum('amount') - (float) $query->sum('paid_amount');

        return [
            Stat::make('Cuotas vencidas', (clone $overdue)->count())
                ->description('USD '.number_format($outstanding($overdue), 2).' por cobrar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Vencen en 7 días', (clone $upcoming)->count())
                ->description('USD '.number_format($outstanding($upcoming), 2).' por cobrar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
