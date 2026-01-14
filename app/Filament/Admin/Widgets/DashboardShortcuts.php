<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Filament\Admin\Resources\Transactions\TransactionResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardShortcuts extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Nuevo Lead', 'Capturar Interesado')
                ->description('Registrar nuevo contacto')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->url(LeadResource::getUrl('create')),

            Stat::make('Nuevo Expediente', 'Iniciar Venta')
                ->description('Crear reserva o presupuesto')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success')
                ->url(BookingResource::getUrl('create')),

            Stat::make('Registrar Pago', 'Caja')
                ->description('Ingresar cobro o pago')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->url(TransactionResource::getUrl('create')),
        ];
    }
}
