<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingDepartures extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Próximas Salidas Confirmadas';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('status', '!=', BookingStatus::Presupuesto)
                    ->where('travel_date', '>=', now())
                    ->orderBy('travel_date', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('travel_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_number')
                    ->label('Expediente'),
                Tables\Columns\TextColumn::make('holder_name')
                    ->label('Pasajeros'),
                Tables\Columns\TextColumn::make('lead.customer_phone')
                    ->label('Contacto'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->paginated(false);
    }
}
