<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BookingItem;
use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Proveedores', Supplier::count())
                ->description('Registrados en sistema')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Servicios Tercerizados', BookingItem::whereNotNull('supplier_id')->count())
                ->description('Items vinculados')
                ->descriptionIcon('heroicon-m-link')
                ->color('success'),

            Stat::make('Volumen de Costos (Global)', '$'.number_format(BookingItem::sum('cost_usd'), 2))
                ->description('Total histórico costos USD')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
