<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Enums\Channel;
use App\Models\Booking;
use App\Models\Lead;
use App\Support\VisitOrigin;
use Filament\Widgets\ChartWidget;

/**
 * Leads → quotes → confirmed bookings, split by the channel the customer first wrote from.
 */
class ChannelFunnelWidget extends ChartWidget
{
    protected ?string $heading = 'Embudo por canal (últimos 90 días)';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_leads', 'manage_bookings']) ?? false;
    }

    protected function getData(): array
    {
        $since = now()->subDays(90);
        $channels = Channel::cases();

        $leads = Lead::where('created_at', '>=', $since)->get(['id', 'channel', 'source', 'utm_source']);
        $bookings = Booking::whereIn('lead_id', $leads->pluck('id'))->get(['lead_id', 'status'])->groupBy('lead_id');

        $rows = collect($channels)->mapWithKeys(fn (Channel $channel) => [$channel->value => ['leads' => 0, 'quotes' => 0, 'confirmed' => 0]])->all();

        foreach ($leads as $lead) {
            $channel = $lead->conversationChannel()->value;

            // Web visits that came from a social network count under "Web · Instagram", etc.
            if ($channel === Channel::Web->value && isset(VisitOrigin::LABELS[$lead->utm_source])) {
                $channel = 'web:'.$lead->utm_source;
                $rows[$channel] ??= ['leads' => 0, 'quotes' => 0, 'confirmed' => 0];
            }

            $rows[$channel]['leads']++;

            foreach ($bookings->get($lead->id, collect()) as $booking) {
                $rows[$channel]['quotes']++;

                if (in_array($booking->status, [BookingStatus::Senado, BookingStatus::Emitido], true)) {
                    $rows[$channel]['confirmed']++;
                }
            }
        }

        $rows = array_filter($rows, fn (array $row) => $row['leads'] > 0);

        return [
            'datasets' => [
                ['label' => 'Leads', 'data' => array_column($rows, 'leads'), 'backgroundColor' => '#f59e0b'],
                ['label' => 'Presupuestos', 'data' => array_column($rows, 'quotes'), 'backgroundColor' => '#3b82f6'],
                ['label' => 'Reservas confirmadas', 'data' => array_column($rows, 'confirmed'), 'backgroundColor' => '#10b981'],
            ],
            'labels' => array_map(fn (string $value) => str_starts_with($value, 'web:') ? 'Web · '.VisitOrigin::LABELS[substr($value, 4)] : Channel::from($value)->getLabel(), array_keys($rows)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
