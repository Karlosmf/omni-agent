<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Support\LeadOutcomeStats;
use Filament\Widgets\Widget;

/**
 * Inquiries received in a period and how they ended (won, lost, still open), with links to the
 * filtered list so the open and lost ones can be followed up.
 */
class LeadOutcomesWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.lead-outcomes';

    protected int|string|array $columnSpan = 'full';

    public string $period = 'this_month';

    public ?string $from = null;

    public ?string $until = null;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_leads', 'manage_bookings']) ?? false;
    }

    public function stats(): LeadOutcomeStats
    {
        return LeadOutcomeStats::forPeriod($this->period, $this->from, $this->until, auth()->user());
    }

    /**
     * The lead list on the given tab, limited to the period and to any extra filter.
     *
     * @param  array<string, array<string, mixed>>  $filters
     */
    public function listUrl(LeadOutcomeStats $stats, string $tab, array $filters = []): string
    {
        return LeadResource::getUrl('index', [
            'tab' => $tab,
            'filters' => [
                'created_at' => [
                    'created_from' => $stats->from->toDateString(),
                    'created_until' => $stats->until->toDateString(),
                ],
                ...$filters,
            ],
        ]);
    }

    protected function getViewData(): array
    {
        $stats = $this->stats();

        return [
            'stats' => $stats,
            'totals' => $stats->totals(),
            'bySource' => $stats->bySource(),
            'byAgent' => $stats->byAgent(),
            'byLostReason' => $stats->byLostReason(),
            'periods' => LeadOutcomeStats::PERIODS,
        ];
    }
}
