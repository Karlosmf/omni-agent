<?php

namespace App\Filament\Admin\Resources\Quotations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Detalles del Presupuesto')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('quotation_number')->label('Número'),
                        TextEntry::make('status')->label('Estado')->badge(),
                        TextEntry::make('valid_until')->label('Válido Hasta')->date('d/m/Y'),
                        TextEntry::make('customer.name')->label('Cliente'),
                        TextEntry::make('destination')->label('Destino'),
                        TextEntry::make('travel_date')->label('Fecha Viaje')->date('d/m/Y'),
                    ]),

                \Filament\Schemas\Components\Section::make('Servicios')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('Items')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        TextEntry::make('description')->label('Descripción')->columnSpan(2),
                                        TextEntry::make('sell')->label('Precio')->money('USD'),
                                    ]),
                            ])
                            ->columns(3),
                    ]),

                \Filament\Schemas\Components\Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_cost')->label('Costo Total')->money('USD'),
                        TextEntry::make('total_sell')->label('Venta Total')->money('USD'),
                        TextEntry::make('profit')->label('Ganancia Estimada')->money('USD')
                            ->color('success')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),
                    ]),

                \Filament\Schemas\Components\Section::make('Notas')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas Internas')
                            ->prose(),
                    ])
                    ->visible(fn($record) => !empty($record->notes)),
            ]);
    }
}
