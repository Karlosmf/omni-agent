<?php

namespace App\Filament\Exporters;

use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('customer_name')
                ->label('Nombre'),
            ExportColumn::make('customer_phone')
                ->label('Teléfono'),
            ExportColumn::make('customer_email')
                ->label('Email'),
            ExportColumn::make('customer_budget')
                ->label('Presupuesto'),
            ExportColumn::make('source')
                ->label('Origen'),
            ExportColumn::make('status')
                ->label('Estado'),
            ExportColumn::make('ai_summary')
                ->label('Resumen IA'),
            ExportColumn::make('needs_human_attention')
                ->label('Requiere Atención')
                ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
            ExportColumn::make('created_at')
                ->label('Fecha de Creación'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Tu exportación de consultas ha finalizado y ' . number_format($export->successful_rows) . ' ' . str('fila')->plural($export->successful_rows) . ' exportadas.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('fila')->plural($failedRowsCount) . ' falló al exportar.';
        }

        return $body;
    }
}
