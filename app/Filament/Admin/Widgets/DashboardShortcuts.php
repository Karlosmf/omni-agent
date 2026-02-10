<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Filament\Admin\Resources\Transactions\TransactionResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardShortcuts extends StatsOverviewWidget
{
    protected static bool $shouldRegisterWidget = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Nueva Consulta', 'Capturar Interesado')
                ->description('Registrar nuevo contacto')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary')
                ->url(LeadResource::getUrl('create')),

            Stat::make('Nuevo File', 'Iniciar Venta')
                ->description('Crear reserva o presupuesto')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success')
                ->url(BookingResource::getUrl('create')),

            Stat::make('Nuevo Movimiento', 'Caja')
                ->description('Ingresar cobro o pago')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->url(TransactionResource::getUrl('create')),

            Stat::make('Recibos', 'Documentación')
                ->description('Ver y descargar comprobantes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url(TransactionResource::getUrl('index')),
        ];
    }
}
