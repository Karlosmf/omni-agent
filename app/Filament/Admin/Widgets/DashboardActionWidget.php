<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class DashboardActionWidget extends Widget
{
    protected static ?int $sort = -5; // Force top position

    protected string $view = 'filament.admin.widgets.dashboard-action-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'xl' => 'full',
    ];
}
