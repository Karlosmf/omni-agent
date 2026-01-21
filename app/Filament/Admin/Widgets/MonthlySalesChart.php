<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlySalesChart extends ChartWidget
{
    protected ?string $heading = 'Ventas Mensuales (USD)';

    protected function getData(): array
    {
        $data = Booking::select(
            DB::raw('sum(total_sell) as total'),
            DB::raw("strftime('%m', created_at) as month")
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $values = [];

        foreach ($months as $month) {
            $values[] = $data[$month] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ventas (USD)',
                    'data' => $values,
                    'borderColor' => '#10b981',
                    'fill' => 'start',
                ],
            ],
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
