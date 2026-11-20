<?php

namespace App\Jobs;

use App\Actions\Packages\ImportPackageAction;
use App\Ai\AgencyAi;
use App\Enums\AiProvider;
use App\Enums\PackageImportStatus;
use App\Models\PackageImport;
use App\Services\Images\ImageSearchService;
use App\Services\Import\PdfTextExtractor;
use App\Services\Import\TravelPackageExtractor;
use App\Services\Import\UrlExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessPackageImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 240;

    public function __construct(public PackageImport $import) {}

    public function handle(
        UrlExtractor $urlExtractor,
        TravelPackageExtractor $extractor,
        ImportPackageAction $action,
        ImageSearchService $images,
    ): void {
        $this->import->update(['status' => PackageImportStatus::Extracting, 'error' => null]);

        try {
            $text = null;
            $pdfPath = null;
            $sourceImages = [];

            switch ($this->import->source_type) {
                case 'url':
                    $page = $urlExtractor->extract((string) $this->import->source_url);
                    $text = $page['text'];
                    $sourceImages = $page['images'];
                    break;
                case 'pdf':
                    $pdfPath = Storage::disk('local')->path((string) $this->import->source_file);

                    // Providers that cannot see PDFs (Groq) get the text of the PDF instead.
                    $provider = app(AgencyAi::class)->provider();

                    if ($provider !== AiProvider::None && ! $provider->supportsPdf()) {
                        $text = mb_substr(app(PdfTextExtractor::class)->extract($pdfPath), 0, UrlExtractor::MAX_CHARS);
                        $pdfPath = null;
                    }

                    break;
                default:
                    $text = mb_substr((string) $this->import->source_text, 0, UrlExtractor::MAX_CHARS);
            }

            $this->import->update(['extracted_text' => $text]);

            $payload = $extractor->extract($text, $pdfPath);
            $action->createDraft($this->import, $payload);

            $candidates = [];
            if ($this->import->use_source_images) {
                foreach ($sourceImages as $i => $url) {
                    $candidates[] = [
                        'id' => 'source:'.$i,
                        'provider' => 'Fuente original',
                        'url' => $url,
                        'thumb' => $url,
                        'author' => null,
                        'author_url' => null,
                        'license' => 'Imagen de la fuente, usada con autorización del agente',
                        'source_url' => $this->import->source_url,
                        'query' => 'fuente',
                    ];
                }
            }

            $candidates = [...$candidates, ...$images->search($payload['image_search_queries'])];

            $this->import->update(['image_candidates' => $candidates, 'status' => PackageImportStatus::ReadyForReview]);
        } catch (Throwable $e) {
            Log::warning('Package import failed: '.$e->getMessage());

            $this->import->update(['status' => PackageImportStatus::Failed, 'error' => $e->getMessage()]);
        }
    }
}
