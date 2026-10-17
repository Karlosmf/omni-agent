<?php

namespace App\Filament\Admin\Resources\PackageImports\Tables;

use App\Filament\Admin\Resources\PackageImports\PackageImportResource;
use App\Models\PackageImport;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PackageImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll(fn () => PackageImport::whereIn('status', ['queued', 'extracting'])->exists() ? '4s' : null)
            ->columns([
                TextColumn::make('travelPackage.title')
                    ->label('Idea')
                    ->placeholder('(procesando…)')
                    ->description(fn (PackageImport $record) => str($record->source_url ?: $record->source_file ?: 'Texto pegado')->limit(60)),
                TextColumn::make('source_type')->label('Fuente')->badge()
                    ->formatStateUsing(fn (string $state) => ['url' => 'Link', 'text' => 'Texto', 'pdf' => 'PDF'][$state] ?? $state),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('creator.name')->label('Creado por')->toggleable(),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Revisar')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PackageImport $record) => PackageImportResource::getUrl('review', ['record' => $record])),
            ])
            ->recordUrl(fn (PackageImport $record) => PackageImportResource::getUrl('review', ['record' => $record]));
    }
}
