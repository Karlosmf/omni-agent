<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\LeadTemperature;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiInsightsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalLeads = Lead::count();
        $hotLeads = Lead::where('temperature', LeadTemperature::Hot)->count();

        // Calculate conversion rate: Leads that have a related booking
        // Since Lead hasOne Booking, we check existence.
        $convertedLeads = Lead::has('booking')->count();
        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        return [
            Stat::make('Total Leads', $totalLeads)
                ->description('Potenciales clientes')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Leads Calientes', $hotLeads)
                ->description('Alta probabilidad')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'), // Red for Hot

            Stat::make('Tasa de Conversión', number_format($conversionRate, 1).'%')
                ->description($convertedLeads.' convertidos')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
