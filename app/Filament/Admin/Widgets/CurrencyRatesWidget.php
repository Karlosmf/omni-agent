<?php

namespace App\Filament\Admin\Widgets;

use App\Services\CurrencyService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class CurrencyRatesWidget extends BaseWidget
{
    protected static ?int $sort = 100;

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
        $service = app(CurrencyService::class);
        $data = $service->getAllData();
        $updatedAt = Carbon::parse($data['updated_at'] ?? now())->format('d/m/y H:i');

        return $table
            ->heading('Cotizaciones BNA')
            ->description("Actualizado: {$updatedAt} hs")
            ->records(fn () => collect($data['currencies'] ?? [])->map(function ($item, $key) {
                return array_merge($item, ['id' => $key]);
            }))
            ->columns([
                TextColumn::make('name')
                    ->label('Moneda')
                    ->weight(FontWeight::Black)
                    ->color('primary'),
                TextColumn::make('buy')
                    ->label('Compra')
                    ->money('ARS', locale: 'es_AR')
                    ->alignEnd(),
                TextColumn::make('sell')
                    ->label('Venta')
                    ->money('ARS', locale: 'es_AR')
                    ->weight(FontWeight::Black)
                    ->alignEnd(),
            ])
            ->headerActions([
                Action::make('sync')
                    ->label('Actualizar')
                    ->icon('heroicon-m-arrow-path')
                    ->size('sm')
                    ->color('gray')
                    ->action(fn () => $this->sync()),
            ])
            ->paginated(false);
    }

    public function sync(): void
    {
        $service = app(CurrencyService::class);

        if ($service->updateLocalRates()) {
            Notification::make()
                ->title('Cotizaciones actualizadas')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error al actualizar')
                ->danger()
                ->send();
        }
    }
}
