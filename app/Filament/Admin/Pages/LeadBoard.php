<?php

namespace App\Filament\Admin\Pages;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Filament\Admin\Resources\Leads\Actions\MarkAsLostAction;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;

/**
 * The sales funnel as columns, to review it and move inquiries from one stage to the next.
 * Each column keeps the same priority order as the list.
 */
class LeadBoard extends Page
{
    /** How many cards a column shows; the rest are counted and found in the list. */
    public const CARDS_PER_COLUMN = 50;

    /** The stages a card can be dragged to. "Por verificar" only shows when the bot left inquiries to confirm. */
    public const DROPPABLE = [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Quoted, LeadStatus::Booked, LeadStatus::Closed];

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-view-columns';

    protected static \UnitEnum|string|null $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Tablero de consultas';

    protected static ?string $title = 'Tablero de consultas';

    protected static ?string $slug = 'tablero';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.admin.pages.lead-board';

    #[Url]
    public bool $onlyMine = false;

    #[Url]
    public bool $onlyHot = false;

    #[Url]
    public bool $showClosed = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('manage_leads') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('Ver lista')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(LeadResource::getUrl()),
        ];
    }

    /**
     * @return list<array{status: LeadStatus, total: int, leads: Collection<int, Lead>}>
     */
    public function columns(): array
    {
        $statuses = [LeadStatus::New, LeadStatus::PendingReview, LeadStatus::Contacted, LeadStatus::Quoted, LeadStatus::Booked];

        if ($this->showClosed) {
            $statuses[] = LeadStatus::Closed;
        }

        $columns = [];

        foreach ($statuses as $status) {
            $query = $this->query()->where('status', $status);
            $total = (clone $query)->count();

            if ($status === LeadStatus::PendingReview && $total === 0) {
                continue;
            }

            $columns[] = [
                'status' => $status,
                'total' => $total,
                'leads' => $query->with(['customer', 'agent'])->orderByPriority()->limit(self::CARDS_PER_COLUMN)->get(),
            ];
        }

        return $columns;
    }

    /**
     * A card dropped on another column. Only leads this user can see and edit move, and only to a funnel stage.
     */
    public function moveLead(int $leadId, string $status): void
    {
        $target = LeadStatus::tryFrom($status);

        if (! $target || ! in_array($target, self::DROPPABLE, true)) {
            return;
        }

        $lead = LeadResource::getEloquentQuery()->find($leadId);

        if (! $lead || Gate::denies('update', $lead) || $lead->status === $target) {
            return;
        }

        // Closing an inquiry that was never sold means it was lost: ask why first.
        if ($target === LeadStatus::Closed && MarkAsLostAction::canBeLost($lead)) {
            $this->mountAction('markLost', ['lead' => $lead->id]);

            return;
        }

        $lead->update(['status' => $target]);

        Notification::make()
            ->title($lead->displayName().' → '.$target->getLabel())
            ->success()
            ->send();
    }

    /**
     * Opened when a card that was never sold is dropped on "Cerrada".
     */
    public function markLostAction(): Action
    {
        return Action::make('markLost')
            ->modalHeading('¿Por qué se perdió la consulta?')
            ->modalSubmitActionLabel('Marcar como perdida')
            ->schema(MarkAsLostAction::schema())
            ->action(function (array $data, array $arguments): void {
                $lead = LeadResource::getEloquentQuery()->find($arguments['lead'] ?? null);

                if (! $lead || Gate::denies('update', $lead)) {
                    return;
                }

                $lead->markAsLost(MarkAsLostAction::reason($data['lost_reason']), $data['lost_note'] ?? null);

                Notification::make()
                    ->title($lead->displayName().' → Perdida')
                    ->success()
                    ->send();
            });
    }

    public function editUrl(Lead $lead): string
    {
        return LeadResource::getUrl('edit', ['record' => $lead]);
    }

    /**
     * @return Builder<Lead>
     */
    private function query(): Builder
    {
        return LeadResource::getEloquentQuery()
            ->when($this->onlyMine, fn (Builder $query) => $query->where('agent_id', auth()->id()))
            ->when($this->onlyHot, fn (Builder $query) => $query->where('temperature', LeadTemperature::Hot));
    }
}
