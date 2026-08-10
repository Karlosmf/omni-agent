<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\Analytics\FunnelStatsWidget;
use App\Filament\Admin\Widgets\Analytics\SourcesChartWidget;
use App\Filament\Admin\Widgets\Analytics\TopProductsWidget;
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
            FunnelStatsWidget::class,
            SourcesChartWidget::class,
            TopProductsWidget::class,
        ];
    }
}
