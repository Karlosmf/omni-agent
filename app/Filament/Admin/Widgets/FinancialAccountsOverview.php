<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\Currency;
use App\Models\FinancialAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialAccountsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = [];
        $totalArs = 0;
        $totalUsd = 0;

        $accounts = FinancialAccount::where('is_active', true)->get();

        foreach ($accounts as $account) {
            $formattedBalance = $account->currency === Currency::USD->value
                ? 'USD '.number_format($account->balance, 2)
                : '$ '.number_format($account->balance, 2);

            $stats[] = Stat::make($account->name, $formattedBalance)
                ->description('Saldo Actual')
                ->descriptionIcon('heroicon-m-wallet')
                ->color($account->balance >= 0 ? 'success' : 'danger');

            if ($account->currency === Currency::ARS->value) {
                $totalArs += $account->balance;
            } elseif ($account->currency === Currency::USD->value) {
                $totalUsd += $account->balance;
            }
        }

        // Add Totals
        $stats[] = Stat::make('Total Consolidado (ARS)', '$ '.number_format($totalArs, 2))
            ->description('Suma de Cajas ARS')
            ->color('info');

        $stats[] = Stat::make('Total Consolidado (USD)', 'USD '.number_format($totalUsd, 2))
            ->description('Suma de Cajas USD')
            ->color('info');

        return $stats;
    }
}
