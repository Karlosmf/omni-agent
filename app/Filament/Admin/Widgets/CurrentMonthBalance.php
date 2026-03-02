<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;

class CurrentMonthBalance extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $now = now();

        $income = \App\Models\Transaction::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->where('type', \App\Enums\TransactionType::Cobro)
            ->sum('amount');

        $expense = \App\Models\Transaction::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->where('type', \App\Enums\TransactionType::Pago)
            ->sum('amount');

        $balance = $income - $expense;

        return [
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Ingresos (Mes Actual)', '$ '.number_format($income, 2))
                ->description('Total cobrado este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            \Filament\Widgets\StatsOverviewWidget\Stat::make('Egresos (Mes Actual)', '$ '.number_format($expense, 2))
                ->description('Total pagado este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            \Filament\Widgets\StatsOverviewWidget\Stat::make('Balance Neto', '$ '.number_format($balance, 2))
                ->description($balance >= 0 ? 'Superávit' : 'Déficit')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
