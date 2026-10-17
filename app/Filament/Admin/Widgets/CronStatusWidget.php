<?php

namespace App\Filament\Admin\Widgets;

use App\Support\CronHeartbeat;
use Filament\Widgets\Widget;

class CronStatusWidget extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.cron-status';

    public static function canView(): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            && config('queue.default') !== 'sync'
            && CronHeartbeat::isStale();
    }

    /**
     * @return array{command: string, lastBeat: ?string}
     */
    protected function getViewData(): array
    {
        $profile = config('omni-hosting.profiles.hostinger');

        return [
            'command' => str_replace(['{path}', '{php}'], [base_path(), '/ruta/a/php8.4'], config('omni-hosting.cron_command')),
            'lastBeat' => CronHeartbeat::lastBeat()?->diffForHumans(),
        ];
    }
}
