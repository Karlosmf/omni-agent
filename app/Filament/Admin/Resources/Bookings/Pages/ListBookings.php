<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'borradores' => Tab::make('Borradores')
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Borrador))
                ->badge(fn () => Booking::where('status', BookingStatus::Borrador)->count()),
            'presupuestos' => Tab::make('Presupuestos')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Presupuesto))
                ->badge(fn () => Booking::where('status', BookingStatus::Presupuesto)->count()),
            'files' => Tab::make('Files Activos')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    BookingStatus::Senado,
                    BookingStatus::Emitido,
                ]))
                ->badge(fn () => Booking::whereIn('status', [
                    BookingStatus::Senado,
                    BookingStatus::Emitido,
                ])->count()),
            'cancelados' => Tab::make('Cancelados')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Cancelado)),
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}
