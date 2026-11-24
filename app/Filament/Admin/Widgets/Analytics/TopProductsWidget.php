<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\TravelPackage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProductsWidget extends TableWidget
{
    protected static ?string $heading = 'Top Paquetes Vendidos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TravelPackage::query()
                    ->select('travel_packages.*')
                    ->selectSub(
                        Booking::query()
                            ->selectRaw('count(*)')
                            ->join('leads', 'leads.id', '=', 'bookings.lead_id')
                            ->whereColumn('leads.travel_package_id', 'travel_packages.id')
                            ->whereIn('bookings.status', BookingStatus::confirmed()),
                        'total',
                    )
                    ->whereIn('travel_packages.id', Booking::query()
                        ->join('leads', 'leads.id', '=', 'bookings.lead_id')
                        ->whereIn('bookings.status', BookingStatus::confirmed())
                        ->select('leads.travel_package_id'))
                    ->orderByDesc('total')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Paquete')
                    ->description(fn (TravelPackage $record): string => $record->destination ?? '')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Ventas')
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
