<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\BookingStatus;
use App\Models\TravelPackage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends TableWidget
{
    protected static ?string $heading = 'Top Paquetes Vendidos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TravelPackage::query()
                    ->select('travel_packages.*', DB::raw('count(bookings.id) as total'))
                    ->join('leads', 'travel_packages.id', '=', 'leads.travel_package_id')
                    ->join('bookings', 'leads.id', '=', 'bookings.lead_id')
                    ->whereIn('bookings.status', [
                        BookingStatus::Senado,
                        BookingStatus::Emitido,
                    ])
                    ->groupBy('travel_packages.id')
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
