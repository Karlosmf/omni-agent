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
            'viajan_pronto' => Tab::make('Viajan en 30 días')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->travellingSoon()->orderBy('travel_date'))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->travellingSoon())),
            'cuotas_vencidas' => Tab::make('Cuotas vencidas')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn (Builder $query) => $query->withOverdueInstallments()->orderBy('travel_date'))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->withOverdueInstallments()))
                ->badgeColor('danger'),
            'sin_vouchers' => Tab::make('Sin vouchers')
                ->icon('heroicon-o-document-minus')
                ->modifyQueryUsing(fn (Builder $query) => $query->missingVouchers()->orderBy('travel_date'))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->missingVouchers()))
                ->badgeColor('warning'),
            'por_vencer' => Tab::make('Presupuestos por vencer')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(fn (Builder $query) => $query->quoteExpiringSoon()->orderBy('valid_until'))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->quoteExpiringSoon())),
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
                ->modifyQueryUsing(fn (Builder $query) => $query->confirmed())
                ->badge(fn () => Booking::confirmed()->count()),
            'cancelados' => Tab::make('Cancelados')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Cancelado)),
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'viajan_pronto';
    }

    /**
     * @param  callable(Builder): mixed  $scope
     */
    private function countWhere(callable $scope): ?int
    {
        $query = BookingResource::getEloquentQuery();
        $scope($query);

        return $query->count() ?: null;
    }
}
