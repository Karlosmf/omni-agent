<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Lead;
use App\Models\Booking;
use App\Enums\BookingStatus;
use Filament\Widgets\ChartWidget;

class FunnelChart extends ChartWidget
{
    protected static ?string $heading = 'Funnel de Conversión';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $totalLeads = Lead::count();
        $totalPresupuestos = Booking::count();
        $totalConfirmados = Booking::whereIn('status', [BookingStatus::Senado, BookingStatus::Emitido])->count();
        
        return [
            'datasets' => [
                [
                    'label' => 'Conversión',
                    'data' => [$totalLeads, $totalPresupuestos, $totalConfirmados],
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981'],
                ],
            ],
            'labels' => ['Leads', 'Presupuestos Creados', 'Reservas Confirmadas'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
