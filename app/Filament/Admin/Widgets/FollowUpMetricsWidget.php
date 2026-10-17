<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FollowUpMetricsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Velocidad de respuesta';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_leads', 'manage_bookings']) ?? false;
    }

    protected function getStats(): array
    {
        $since = now()->subDays(30);

        $contacted = Lead::whereNotNull('last_contacted_at')->where('created_at', '>=', $since)->get(['created_at', 'last_contacted_at']);
        $avgMinutes = $contacted->isEmpty()
            ? null
            : (int) round($contacted->avg(fn (Lead $lead) => $lead->created_at->diffInMinutes($lead->last_contacted_at, true)));

        $sent = Booking::whereHas('activities', fn ($q) => $q->where('type', 'email_sent'))
            ->whereIn('status', [BookingStatus::Presupuesto, BookingStatus::Senado, BookingStatus::Emitido])
            ->where('created_at', '>=', $since);
        $sentCount = (clone $sent)->count();
        $openedCount = (clone $sent)->where('view_count', '>', 0)->count();

        return [
            Stat::make('Tiempo hasta el primer contacto', $avgMinutes === null ? '—' : $this->humanize($avgMinutes))
                ->description('Promedio de los últimos 30 días')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Presupuestos abiertos', $sentCount > 0 ? round($openedCount / $sentCount * 100).'%' : '—')
                ->description("{$openedCount} de {$sentCount} enviados (30 días)")
                ->descriptionIcon('heroicon-m-eye')
                ->color('success'),
        ];
    }

    private function humanize(int $minutes): string
    {
        return $minutes < 120 ? "{$minutes} min" : round($minutes / 60, 1).' h';
    }
}
