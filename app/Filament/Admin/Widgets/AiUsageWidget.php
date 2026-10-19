<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiUsageWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Uso de la IA este mes';

    public static function canView(): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && (get_agency_settings()?->ai_provider?->value ?? 'none') !== 'none';
    }

    protected function getStats(): array
    {
        $month = Message::whereNotNull('input_tokens')->where('created_at', '>=', now()->startOfMonth());
        $replies = (clone $month)->count();
        $tokens = (int) (clone $month)->sum('input_tokens') + (int) (clone $month)->sum('output_tokens');
        $limit = (int) get_agency_settings()?->ai_monthly_message_limit;

        return [
            Stat::make('Respuestas de la IA', number_format($replies, 0, ',', '.'))
                ->description($limit > 0 ? 'Límite mensual: '.number_format($limit, 0, ',', '.').' ('.min(100, (int) round($replies / $limit * 100)).'% usado)' : 'Sin límite mensual')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($limit > 0 && $replies >= $limit ? 'danger' : 'primary'),

            Stat::make('Tokens usados', number_format($tokens, 0, ',', '.'))
                ->description($replies > 0 ? 'Unos '.number_format((int) round($tokens / $replies), 0, ',', '.').' por respuesta' : 'Todavía sin uso')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),
        ];
    }
}
