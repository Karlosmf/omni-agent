<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingDeadlinesWidget extends BaseWidget
{
    protected static ?int $sort = 101; // Next to CurrencyRatesWidget at the bottom

    protected int|string|array $columnSpan = [
        'default' => 1,
        'sm' => 1,
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
        '2xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Próximos Vencimientos (30 días)')
            ->records(fn () => Booking::whereDate('travel_date', '>=', now())
                ->whereDate('travel_date', '<=', now()->addDays(30))
                ->orderBy('travel_date')
                ->limit(5)
                ->get()
                ->map(fn ($booking) => [
                    'id' => $booking->id,
                    'file_number' => $booking->file_number,
                    'holder_name' => $booking->holder_name,
                    'travel_date' => $booking->travel_date,
                    'days_until' => (int) ceil(now()->diffInDays($booking->travel_date, false)),
                    'status' => $booking->status,
                ]))
            ->columns([
                TextColumn::make('file_number')
                    ->label('Exp.')
                    ->weight(FontWeight::Black)
                    ->color('primary')
                    ->description(fn ($record) => $record['holder_name']),
                TextColumn::make('travel_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->alignEnd(),
                TextColumn::make('days_until')
                    ->label('Días')
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color(fn ($state) => match (true) {
                        $state <= 7 => 'danger',
                        $state <= 15 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
