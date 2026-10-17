<?php

namespace App\Filament\Admin\Resources\PackageImports\Pages;

use App\Filament\Admin\Resources\PackageImports\PackageImportResource;
use App\Jobs\ProcessPackageImport;
use App\Services\Import\UrlGuard;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePackageImport extends CreateRecord
{
    protected static string $resource = PackageImportResource::class;

    protected static bool $canCreateAnother = false;

    protected ?string $heading = 'Importar una Idea de Viaje';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['source_type'] ?? null) === 'url') {
            try {
                UrlGuard::assertPublic((string) $data['source_url']);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['data.source_url' => $e->getMessage()]);
            }
        }

        $data['created_by'] = auth()->id();
        $data['status'] = 'queued';

        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessPackageImport::dispatch($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return PackageImportResource::getUrl('review', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Importar')->icon('heroicon-o-sparkles');
    }
}
