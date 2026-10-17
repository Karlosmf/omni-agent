<?php

namespace App\Filament\Admin\Resources\PackageImports\Pages;

use App\Enums\PackageImportStatus;
use App\Filament\Admin\Resources\PackageImports\PackageImportResource;
use App\Filament\Admin\Resources\TravelPackages\TravelPackageResource;
use App\Jobs\ProcessPackageImport;
use App\Models\PackageImport;
use App\Services\Images\ImageDownloader;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Throwable;

class ReviewPackageImport extends Page
{
    protected static string $resource = PackageImportResource::class;

    protected string $view = 'filament.admin.resources.package-imports.pages.review';

    protected static ?string $title = 'Revisar importación';

    public PackageImport $record;

    /** @var list<string> Candidate ids chosen for the gallery. */
    public array $selected = [];

    public ?string $cover = null;

    public function mount(PackageImport $record): void
    {
        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        $package = $this->record->travelPackage;
        $reviewable = $this->record->status === PackageImportStatus::ReadyForReview && $package;

        return [
            Action::make('retry')
                ->label('Reintentar')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->status === PackageImportStatus::Failed)
                ->action(function () {
                    $this->record->update(['status' => PackageImportStatus::Queued, 'error' => null]);
                    ProcessPackageImport::dispatch($this->record);
                }),
            Action::make('edit')
                ->label('Editar borrador')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible($reviewable)
                ->url($package ? TravelPackageResource::getUrl('edit', ['record' => $package]) : null),
            Action::make('publish')
                ->label('Publicar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($reviewable)
                ->requiresConfirmation()
                ->modalDescription('La idea quedará visible en el sitio.')
                ->action(fn () => $this->publish()),
            Action::make('discard')
                ->label('Descartar')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [PackageImportStatus::ReadyForReview, PackageImportStatus::Failed], true))
                ->requiresConfirmation()
                ->action(fn () => $this->discard()),
        ];
    }

    public function applySelectedImages(ImageDownloader $downloader): void
    {
        $package = $this->record->travelPackage;
        $candidates = collect($this->record->image_candidates ?? [])->keyBy('id');
        $chosen = collect($this->selected)->take(8)->map(fn ($id) => $candidates->get($id))->filter();

        if (! $package || $chosen->isEmpty()) {
            Notification::make()->title('Elegí al menos una imagen')->warning()->send();

            return;
        }

        $coverId = $this->cover && $chosen->has($this->cover) ? $this->cover : $chosen->keys()->first();
        $stored = [];
        $failed = 0;

        foreach ($chosen as $id => $candidate) {
            try {
                $stored[$id] = $downloader->store($candidate);
            } catch (Throwable) {
                $failed++;
            }
        }

        if ($stored === []) {
            Notification::make()->title('No se pudo descargar ninguna imagen')->danger()->send();

            return;
        }

        $coverKey = array_key_exists($coverId, $stored) ? $coverId : array_key_first($stored);

        $package->update([
            'cover_image' => $stored[$coverKey]['path'],
            'gallery' => collect($stored)->except($coverKey)->pluck('path')->values()->all(),
            'image_credits' => collect($stored)->pluck('credit')->values()->all(),
        ]);

        $this->record->refresh();

        Notification::make()
            ->title('Imágenes guardadas')
            ->body($failed ? "{$failed} no se pudieron descargar." : null)
            ->success()
            ->send();
    }

    public function publish(): void
    {
        $package = $this->record->travelPackage;

        $missing = collect([
            ! $package?->cover_image ? 'una imagen de portada' : null,
            (float) $package?->price_from <= 0 ? 'el precio' : null,
            blank($package?->summary) ? 'el resumen' : null,
        ])->filter();

        if ($missing->isNotEmpty()) {
            Notification::make()->title('Falta completar antes de publicar')->body('Revisá: '.$missing->implode(', ').'.')->warning()->send();

            return;
        }

        $package->update(['is_active' => true]);
        $this->record->update(['status' => PackageImportStatus::Published]);

        Notification::make()->title('Idea de viaje publicada')->success()->send();
    }

    public function discard(): void
    {
        $package = $this->record->travelPackage;

        if ($package && ! $package->is_active) {
            $package->delete();
        }

        $this->record->update(['status' => PackageImportStatus::Discarded]);

        $this->redirect(PackageImportResource::getUrl('index'));
    }
}
