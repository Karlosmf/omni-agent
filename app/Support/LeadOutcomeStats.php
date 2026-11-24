<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * How the inquiries received in a period ended up: won, lost or still open.
 * An inquiry belongs to the period it was created in, whatever happened to it later.
 */
class LeadOutcomeStats
{
    public const PERIODS = [
        'this_month' => 'Este mes',
        'last_month' => 'Mes pasado',
        'last_30_days' => 'Últimos 30 días',
        'this_year' => 'Este año',
        'custom' => 'Rango',
    ];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $until,
        public readonly ?User $viewer = null,
    ) {}

    /**
     * Builds the stats for one of the {@see self::PERIODS}. A custom range without dates falls back to this month.
     */
    public static function forPeriod(string $period, ?string $from = null, ?string $until = null, ?User $viewer = null): self
    {
        $now = CarbonImmutable::now();

        [$start, $end] = match ($period) {
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'last_30_days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            'custom' => [
                $from ? CarbonImmutable::parse($from)->startOfDay() : $now->startOfMonth(),
                $until ? CarbonImmutable::parse($until)->endOfDay() : $now->endOfDay(),
            ],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return new self($start, $end, $viewer);
    }

    /**
     * @return array{received: int, won: int, lost: int, open: int, conversion: float}
     */
    public function totals(): array
    {
        $row = $this->aggregate()->toBase()->first();

        return $this->row($row);
    }

    /**
     * @return list<array{source: ?string, label: string, received: int, won: int, lost: int, open: int, conversion: float}>
     */
    public function bySource(): array
    {
        return $this->aggregate('source')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => ['source' => $row->source, 'label' => Lead::sourceLabel($row->source)] + $this->row($row))
            ->sortByDesc('received')
            ->values()
            ->all();
    }

    /**
     * @return list<array{agent_id: ?int, label: string, received: int, won: int, lost: int, open: int, conversion: float}>
     */
    public function byAgent(): array
    {
        $rows = $this->aggregate('agent_id')->toBase()->get();
        $names = User::whereIn('id', $rows->pluck('agent_id')->filter())->pluck('name', 'id');

        return $rows
            ->map(fn (object $row): array => [
                'agent_id' => $row->agent_id === null ? null : (int) $row->agent_id,
                'label' => $row->agent_id === null ? 'Sin asignar' : ($names[$row->agent_id] ?? 'Usuario eliminado'),
            ] + $this->row($row))
            ->sortByDesc('received')
            ->values()
            ->all();
    }

    /**
     * Inquiries of the period this user may see.
     *
     * @return Builder<Lead>
     */
    public function query(): Builder
    {
        return Lead::query()
            ->visibleTo($this->viewer)
            ->whereBetween('leads.created_at', [$this->from, $this->until]);
    }

    /**
     * Counts with conditional sums, grouped only by a plain column so it runs under ONLY_FULL_GROUP_BY
     * (MySQL, MariaDB) and on SQLite alike.
     *
     * @return Builder<Lead>
     */
    private function aggregate(?string $groupColumn = null): Builder
    {
        $bookings = (new Booking)->getTable();
        $confirmed = array_map(fn (BookingStatus $status): string => $status->value, BookingStatus::confirmed());
        $hasSale = "exists (select 1 from {$bookings} where {$bookings}.lead_id = leads.id and {$bookings}.status in (?, ?))";

        $query = $this->query()->selectRaw(
            'count(*) as received, '
            ."sum(case when leads.status = ? or {$hasSale} then 1 else 0 end) as won, "
            ."sum(case when leads.status = ? and not {$hasSale} then 1 else 0 end) as lost",
            [LeadStatus::Booked->value, ...$confirmed, LeadStatus::Closed->value, ...$confirmed],
        );

        if ($groupColumn !== null) {
            $query->addSelect("leads.{$groupColumn}")->groupBy("leads.{$groupColumn}");
        }

        return $query;
    }

    /**
     * @return array{received: int, won: int, lost: int, open: int, conversion: float}
     */
    private function row(?object $row): array
    {
        $received = (int) ($row->received ?? 0);
        $won = (int) ($row->won ?? 0);
        $lost = (int) ($row->lost ?? 0);

        return [
            'received' => $received,
            'won' => $won,
            'lost' => $lost,
            'open' => $received - $won - $lost,
            'conversion' => $received > 0 ? round($won / $received * 100, 1) : 0.0,
        ];
    }
}
