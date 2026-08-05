<?php

namespace App\Filament\Exports;

use App\Models\Booking;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BookingExporter extends Exporter
{
    protected static ?string $model = Booking::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('file_number')->label('Expediente'),
            ExportColumn::make('customer.first_name')->label('Cliente'),
            ExportColumn::make('holder_name')->label('Titular'),
            ExportColumn::make('destination')->label('Destino'),
            ExportColumn::make('travel_date')->label('Fecha Viaje'),
            ExportColumn::make('passengers')->label('Pax'),
            ExportColumn::make('nights')->label('Noches'),
            ExportColumn::make('status')->label('Estado'),
            ExportColumn::make('currency')->label('Moneda'),
            ExportColumn::make('total_cost')->label('Costo Total'),
            ExportColumn::make('total_sell')->label('Venta Total'),
            ExportColumn::make('profit')->label('Rentabilidad')
                ->state(fn (Booking $record) => $record->calculateProfit()),
            ExportColumn::make('created_at')->label('Creado'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de expedientes ha finalizado. Se han exportado '.Number::format($export->successful_rows).' '.str('fila')->plural($export->successful_rows).'.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron al exportar.';
        }

        return $body;
    }
}
