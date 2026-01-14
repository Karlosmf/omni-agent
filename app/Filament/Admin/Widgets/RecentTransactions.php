<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactions extends BaseWidget
{
    protected static ?string $heading = 'Últimos Movimientos de Caja';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m H:i'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->formatStateUsing(fn ($record) => $record->amount.' '.$record->currency->value),
                Tables\Columns\TextColumn::make('booking.holder_name')
                    ->label('Cliente')
                    ->limit(15),
            ])
            ->paginated(false);
    }
}
