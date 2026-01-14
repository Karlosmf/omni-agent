<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\LeadTemperature;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HotLeadsCount extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Leads Calientes', Lead::where('temperature', LeadTemperature::Hot)->count())
                ->description('Requieren atención inmediata')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
            Stat::make('Atención Necesaria', Lead::where('needs_human_attention', true)->count())
                ->description('Marcados para intervención manual')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            Stat::make('Total Leads Nuevos', Lead::where('status', 'new')->count())
                ->description('Esperando ser procesados')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }
}
