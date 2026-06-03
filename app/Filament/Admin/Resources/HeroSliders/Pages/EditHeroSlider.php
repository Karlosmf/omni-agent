<?php

namespace App\Filament\Admin\Resources\HeroSliders\Pages;

use App\Filament\Admin\Resources\HeroSliders\HeroSliderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHeroSlider extends EditRecord
{
    protected static string $resource = HeroSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['image_type'])) {
            if ($data['image_type'] === 'predefined' && isset($data['image_path_predefined'])) {
                $data['image_path'] = $data['image_path_predefined'];
            } elseif ($data['image_type'] === 'url' && isset($data['image_path_url'])) {
                $data['image_path'] = $data['image_path_url'];
            }
        }

        unset($data['image_type']);
        unset($data['image_path_predefined']);
        unset($data['image_path_url']);

        return $data;
    }
}
