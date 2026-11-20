<?php

namespace App\Filament\Admin\Resources\BotNotices\Pages;

use App\Filament\Admin\Resources\BotNotices\BotNoticeResource;
use Filament\Resources\Pages\ListRecords;

class ListBotNotices extends ListRecords
{
    protected static string $resource = BotNoticeResource::class;
}
