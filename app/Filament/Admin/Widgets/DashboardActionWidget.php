<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class DashboardActionWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.dashboard-action-widget';

    protected int|string|array $columnSpan = 'full';
}
