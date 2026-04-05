<?php

namespace App\Filament\Admin\Resources\HeroSliders\Pages;

use App\Filament\Admin\Resources\HeroSliders\HeroSliderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHeroSliders extends ListRecords
{
    protected static string $resource = HeroSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
