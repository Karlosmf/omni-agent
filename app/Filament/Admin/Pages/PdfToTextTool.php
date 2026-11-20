<?php

namespace App\Filament\Admin\Pages;

use App\Services\Import\PdfTextExtractor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfToTextTool extends Page
{
    use WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Convertir PDF a texto';

    protected static ?string $title = 'Convertir PDF a texto';

    protected static ?string $slug = 'pdf-a-texto';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.admin.pages.pdf-to-text-tool';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public string $text = '';

    public string $filename = 'documento';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_packages', 'manage_leads', 'manage_bookings']) ?? false;
    }

    public function convert(PdfTextExtractor $extractor): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:pdf', 'max:25600']], [
            'file.required' => 'Elegí un archivo PDF.',
            'file.mimes' => 'El archivo tiene que ser un PDF.',
            'file.max' => 'El PDF es demasiado grande (máximo 25 MB).',
        ]);

        $this->text = '';

        try {
            $this->text = $extractor->extract($this->file->getRealPath());
            $this->filename = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'documento';
        } catch (RuntimeException $e) {
            Notification::make()->title('No se pudo convertir')->body($e->getMessage())->danger()->send();
        }
    }

    public function download(): ?StreamedResponse
    {
        if ($this->text === '') {
            return null;
        }

        $name = Str::slug($this->filename) ?: 'documento';

        return response()->streamDownload(fn () => print ($this->text), $name.'.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function clear(): void
    {
        $this->reset('file', 'text');
    }
}
