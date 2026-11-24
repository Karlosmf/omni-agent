<?php

namespace App\Filament\Admin\Resources\Leads\Pages;

use App\Enums\LeadTemperature;
use App\Filament\Admin\Pages\LeadBoard;
use App\Filament\Admin\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label('Ver tablero')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(LeadBoard::getUrl()),
            CreateAction::make(),
        ];
    }

    /**
     * Work queues, most urgent first. Counts follow what this user may see.
     */
    public function getTabs(): array
    {
        return [
            'hoy' => Tab::make('Para hoy')
                ->icon('heroicon-o-bolt')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsActionToday())
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->needsActionToday()))
                ->badgeColor('danger'),
            'vencidos' => Tab::make('Seguimiento vencido')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->followUpOverdue())
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->followUpOverdue()))
                ->badgeColor('warning'),
            'calientes' => Tab::make('Calientes')
                ->icon('heroicon-o-fire')
                ->modifyQueryUsing(fn (Builder $query) => $query->open()->where('temperature', LeadTemperature::Hot))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->open()->where('temperature', LeadTemperature::Hot))),
            'sin_asignar' => Tab::make('Sin asignar')
                ->icon('heroicon-o-user-minus')
                ->modifyQueryUsing(fn (Builder $query) => $query->open()->whereNull('agent_id'))
                ->badge(fn () => $this->countWhere(fn (Builder $query) => $query->open()->whereNull('agent_id'))),
            'abiertas' => Tab::make('Abiertas')
                ->icon('heroicon-o-inbox')
                ->modifyQueryUsing(fn (Builder $query) => $query->pendingOutcome()),
            'ganadas' => Tab::make('Concretadas')
                ->icon('heroicon-o-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->won()),
            'perdidas' => Tab::make('Perdidas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->lost()),
            'todas' => Tab::make('Todas')
                ->icon('heroicon-o-list-bullet'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'hoy';
    }

    /**
     * @param  callable(Builder): mixed  $scope
     */
    private function countWhere(callable $scope): ?int
    {
        $query = LeadResource::getEloquentQuery();
        $scope($query);

        return $query->count() ?: null;
    }
}
