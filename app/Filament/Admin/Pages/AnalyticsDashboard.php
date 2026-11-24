<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\Analytics\FunnelStatsWidget;
use App\Filament\Admin\Widgets\Analytics\LeadOutcomesWidget;
use App\Filament\Admin\Widgets\Analytics\TopProductsWidget;
use App\Filament\Admin\Widgets\LeadSourcesChart;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class AnalyticsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Analytics & Funnel';

    protected static ?string $title = 'Métricas y Conversión';

    protected static UnitEnum|string|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            LeadOutcomesWidget::class,
            FunnelStatsWidget::class,
            LeadSourcesChart::class,
            TopProductsWidget::class,
        ];
    }
}
