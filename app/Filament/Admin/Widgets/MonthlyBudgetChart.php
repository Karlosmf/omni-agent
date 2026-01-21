<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class MonthlyBudgetChart extends ChartWidget
{
    protected ?string $heading = 'Resultados Financieros (Año Actual)';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $year = now()->year;

        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        $monthExpression = $isSqlite
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $data = \App\Models\Transaction::query()
            ->selectRaw("{$monthExpression} as month, type, SUM(amount) as total")
            ->whereYear('created_at', $year)
            ->groupBy('month', 'type')
            ->get();

        $incomes = [];
        $expenses = [];

        // Initialize 12 months with 0
        for ($i = 1; $i <= 12; $i++) {
            $incomes[$i] = 0;
            $expenses[$i] = 0;
        }

        foreach ($data as $row) {
            if ($row->type === \App\Enums\TransactionType::Cobro) {
                $incomes[$row->month] = $row->total;
            } elseif ($row->type === \App\Enums\TransactionType::Pago) {
                $expenses[$row->month] = $row->total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => array_values($incomes),
                    'backgroundColor' => '#22c55e', // vert-500
                    'borderColor' => '#22c55e',
                ],
                [
                    'label' => 'Egresos',
                    'data' => array_values($expenses),
                    'backgroundColor' => '#ef4444', // red-500
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
