<?php

namespace App\Filament\Admin\Pages;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Lead;
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

    protected string $view = 'filament.admin.pages.analytics-dashboard';

    protected function getViewData(): array
    {
        $totalLeads = Lead::count();
        $totalCotizados = Booking::count();
        $totalConfirmados = Booking::whereIn('status', [
            BookingStatus::Senado,
            BookingStatus::Emitido,
        ])->count();

        // Conversions
        $cotizacionRate = $totalLeads > 0 ? round(($totalCotizados / $totalLeads) * 100, 1) : 0;
        $confirmacionRate = $totalCotizados > 0 ? round(($totalConfirmados / $totalCotizados) * 100, 1) : 0;

        // Sources
        $sources = Lead::select('source', \DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        // Top Products
        $topProducts = Booking::select('travel_package_id', \DB::raw('count(*) as total'))
            ->whereNotNull('travel_package_id')
            ->whereIn('status', [
                BookingStatus::Senado,
                BookingStatus::Emitido,
            ])
            ->groupBy('travel_package_id')
            ->orderByDesc('total')
            ->with('travelPackage')
            ->take(5)
            ->get();

        return [
            'totalLeads' => $totalLeads,
            'totalCotizados' => $totalCotizados,
            'totalConfirmados' => $totalConfirmados,
            'cotizacionRate' => $cotizacionRate,
            'confirmacionRate' => $confirmacionRate,
            'sources' => $sources,
            'topProducts' => $topProducts,
        ];
    }
}
