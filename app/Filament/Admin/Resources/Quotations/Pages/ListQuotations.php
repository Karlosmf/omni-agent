<?php

namespace App\Filament\Admin\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Admin\Resources\Quotations\QuotationResource;
use App\Models\Quotation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pendientes' => Tab::make('Pendientes')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    QuotationStatus::Draft,
                    QuotationStatus::Sent,
                ]))
                ->badge(fn () => Quotation::whereIn('status', [
                    QuotationStatus::Draft,
                    QuotationStatus::Sent,
                ])->count()),
            'aprobados' => Tab::make('Aprobados (Files)')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', QuotationStatus::Accepted))
                ->badge(fn () => Quotation::where('status', QuotationStatus::Accepted)->count()),
            'rechazados' => Tab::make('Rechazados / Expirados')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    QuotationStatus::Rejected,
                    QuotationStatus::Expired,
                ])),
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}
