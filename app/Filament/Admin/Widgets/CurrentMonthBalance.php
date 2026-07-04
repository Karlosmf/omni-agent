<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentMonthBalance extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'xl' => 'full',
    ];

    protected function getStats(): array
    {
        $now = now();

        $income = Transaction::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->where('type', TransactionType::Cobro)
            ->sum('amount');

        $expense = Transaction::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->where('type', TransactionType::Pago)
            ->sum('amount');

        $balance = $income - $expense;

        return [
            Stat::make('Ingresos (Mes Actual)', '$ '.number_format($income, 2))
                ->description('Total cobrado este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Egresos (Mes Actual)', '$ '.number_format($expense, 2))
                ->description('Total pagado este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Balance Neto', '$ '.number_format($balance, 2))
                ->description($balance >= 0 ? 'Superávit' : 'Déficit')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
