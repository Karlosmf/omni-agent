<?php

namespace App\Filament\Admin\Widgets;

use App\Models\SupplierAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierDebtsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $accounts = SupplierAccount::with('supplier')->get();

        $totalDebtUsd = $accounts->sum(fn ($account) => $account->balance < 0 ? abs($account->balance) : 0);
        $totalDebtArs = 0; // TODO: Implement when ARS accounts are added

        $suppliersWithDebt = $accounts->filter(fn ($account) => $account->balance < 0)->count();

        return [
            Stat::make('Deuda Total (USD)', '$'.number_format($totalDebtUsd, 2))
                ->description('Total adeudado a proveedores')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),

            Stat::make('Proveedores con Saldo Pendiente', $suppliersWithDebt)
                ->description('Cuentas a pagar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make(
                'Mayor Deuda Individual',
                $accounts->min('balance') < 0
                ? '$'.number_format(abs($accounts->min('balance')), 2)
                : '$0.00'
            )
                ->description(
                    $accounts->sortBy('balance')->first()?->supplier?->name ?? 'N/A'
                )
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];
    }
}
