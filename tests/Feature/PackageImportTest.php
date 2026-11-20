<?php

use App\Actions\Packages\ImportPackageAction;
use App\Enums\AiProvider;
use App\Enums\PackageImportStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\PackageImports\Pages\CreatePackageImport;
use App\Filament\Admin\Resources\PackageImports\Pages\ReviewPackageImport;
use App\Jobs\ProcessPackageImport;
use App\Models\AgencySetting;
use App\Models\PackageImport;
use App\Models\TravelPackage;
use App\Models\User;
use App\Services\AiConciergeService;
use App\Services\Images\ImageDownloader;
use App\Services\Images\ImageProvider;
use App\Services\Images\ImageSearchService;
use App\Services\Images\PexelsProvider;
use App\Services\Images\UnsplashProvider;
use App\Services\Images\WikimediaProvider;
use App\Services\Import\SafeHttp;
use App\Services\Import\TravelPackageExtractor;
use App\Services\Import\UrlExtractor;
use App\Services\Import\UrlGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function aiJson(array $override = []): string
{
    return json_encode($override + [
        'title' => 'Buenos Aires Clásico',
        'destination' => 'Buenos Aires',
        'country' => 'Argentina',
        'nights' => 4,
        'summary' => 'Cuatro noches para conocer la ciudad.',
        'description' => 'Una escapada para recorrer los clásicos porteños.',
        'itinerary' => [['day' => 1, 'title' => 'Llegada', 'description' => 'Traslado y city tour.']],
        'included' => ['Traslados', 'Desayuno'],
        'excluded' => ['Aéreos'],
        'price_from' => 450,
        'currency' => 'USD',
        'price_basis' => 'por_persona_base_doble',
        'seasons' => [['name' => 'Alta', 'from' => '2026-12-01', 'to' => '2027-02-28', 'price_from' => 520], ['name' => 'rota', 'from' => 'diciembre', 'to' => 'x', 'price_from' => 1]],
        'tags' => ['ciudad'],
        'image_search_queries' => ['Obelisco Buenos Aires'],
        'confidence' => ['price' => 'baja', 'nights' => 'alta'],
    ]);
}

function fakeImageProvider(array $candidates): ImageProvider
{
    return new class($candidates) implements ImageProvider
    {
        public function __construct(private array $candidates) {}

        public function isConfigured(): bool
        {
            return true;
        }

        public function search(string $query, int $limit = 6): array
        {
            return $this->candidates;
        }
    };
}

function pngBytes(): string
{
    ob_start();
    imagepng(imagecreatetruecolor(1200, 800));

    return (string) ob_get_clean();
}

function candidate(string $id = 'unsplash:1', array $extra = []): array
{
    return $extra + [
        'id' => $id, 'provider' => 'Unsplash', 'url' => 'https://93.184.216.34/photo-'.$id.'.jpg', 'thumb' => 'https://93.184.216.34/t.jpg',
        'author' => 'Juan Pérez', 'author_url' => 'https://unsplash.com/@juan', 'license' => 'Unsplash License', 'source_url' => 'https://unsplash.com/photos/1',
        'download_ping' => 'https://api.unsplash.com/photos/1/download', 'query' => 'Obelisco',
    ];
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('uploads');
});

// --- Security ---

test('only public http(s) addresses can be imported', function (string $url) {
    UrlGuard::assertPublic($url);
})->with([
    'localhost' => 'http://127.0.0.1/admin',
    'private network' => 'http://192.168.1.10/',
    'ten network' => 'https://10.0.0.5/x',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data',
    'ipv6 loopback' => 'http://[::1]/',
    'file scheme' => 'file:///etc/passwd',
    'credentials' => 'https://user:pass@93.184.216.34/',
])->throws(InvalidArgumentException::class);

test('a public address is accepted', function () {
    expect(UrlGuard::assertPublic('https://93.184.216.34/oferta'))->toBe('https://93.184.216.34/oferta');
});

test('a redirect to an internal address is rejected', function () {
    Http::fake(['93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data'])]);

    SafeHttp::get('https://93.184.216.34/oferta');
})->throws(InvalidArgumentException::class);

test('the create form rejects internal addresses', function () {
    Queue::fake();
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(CreatePackageImport::class)
        ->fillForm(['source_type' => 'url', 'source_url' => 'http://127.0.0.1/secret'])
        ->call('create')
        ->assertHasErrors();

    Queue::assertNothingPushed();
    expect(PackageImport::count())->toBe(0);
});

test('creating an import queues the job', function () {
    Queue::fake();
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(CreatePackageImport::class)
        ->fillForm(['source_type' => 'text', 'source_text' => str_repeat('Paquete a Bariloche 5 noches con desayuno. ', 5)])
        ->call('create')
        ->assertHasNoFormErrors();

    Queue::assertPushed(ProcessPackageImport::class);
    expect(PackageImport::first()->status)->toBe(PackageImportStatus::Queued);
});

// --- Text extraction ---

test('the html extractor keeps the offer text and drops navigation, scripts and logos', function () {
    $html = '<html><head><title>Bariloche 5 noches</title><meta property="og:image" content="/img/hero.jpg"></head><body>'
        .'<nav>MENU PRINCIPAL</nav><script>var secret = 1;</script><main><h1>Bariloche</h1><p>'.str_repeat('Cinco noches con desayuno y traslados incluidos. ', 4).'</p>'
        .'<img src="/img/logo.png"><img src="/img/lago.jpg"><img src="/img/icono.svg"></main><footer>Todos los derechos reservados</footer></body></html>';

    $page = (new UrlExtractor)->parseHtml($html, 'https://operador.example/ofertas/bariloche');

    expect($page['text'])->toContain('Bariloche 5 noches')->toContain('desayuno')
        ->not->toContain('MENU PRINCIPAL')->not->toContain('secret')->not->toContain('derechos reservados')
        ->and($page['images'])->toBe(['https://operador.example/img/hero.jpg', 'https://operador.example/img/lago.jpg']);
});

test('a page without enough text is rejected', function () {
    (new UrlExtractor)->parseHtml('<html><body><main>Hola</main></body></html>', 'https://operador.example/');
})->throws(RuntimeException::class, 'casi no tiene texto');

// --- AI extraction ---

test('the extractor accepts fenced JSON and normalizes it', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->andReturn("```json\n".aiJson()."\n```");

    $payload = (new TravelPackageExtractor($ai))->extract('texto de la oferta');

    expect($payload['title'])->toBe('Buenos Aires Clásico')
        ->and($payload['nights'])->toBe(4)
        ->and($payload['price_from'])->toBe(450.0)
        ->and($payload['included'])->toBe(['Traslados', 'Desayuno']);
});

test('the prompt forbids inventing data and asks to remove the source operator', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->withArgs(fn ($prompt) => str_contains($prompt, 'NO inventes') && str_contains($prompt, 'ELIMINÁ el nombre') && str_contains($prompt, 'rioplatense'))->andReturn(aiJson());

    (new TravelPackageExtractor($ai))->extract('texto');
});

test('invalid JSON is retried once and then succeeds', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->twice()->andReturn('esto no es json', aiJson());

    expect((new TravelPackageExtractor($ai))->extract('texto')['destination'])->toBe('Buenos Aires');
});

test('two invalid answers fail the extraction', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->twice()->andReturn('nada', '{"title": "sin destino"}');

    (new TravelPackageExtractor($ai))->extract('texto');
})->throws(RuntimeException::class, 'no devolvió un resultado utilizable');

// --- Draft creation ---

test('the draft is created unpublished with a unique slug and warnings', function () {
    TravelPackage::factory()->create(['title' => 'Buenos Aires Clásico', 'slug' => 'buenos-aires-clasico', 'destination' => 'Buenos Aires', 'nights' => 4]);
    $import = PackageImport::create(['source_type' => 'text', 'source_text' => 'x']);
    $payload = (new TravelPackageExtractor(Mockery::mock(AiConciergeService::class)))->normalize(json_decode(aiJson(['price_from' => null]), true));

    $package = app(ImportPackageAction::class)->createDraft($import, $payload);

    expect($package->is_active)->toBeFalse()
        ->and($package->slug)->toBe('buenos-aires-clasico-2')
        ->and($package->included)->toBe("Traslados\nDesayuno")
        ->and((float) $package->price_from)->toBe(0.0)
        ->and($package->seasons)->toHaveCount(1)
        ->and($package->source_import_id)->toBe($import->id)
        ->and($import->fresh()->warnings)->toHaveCount(2);
});

// --- Job ---

test('the job builds a draft from pasted text and finds real photos', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->andReturn(aiJson());

    $import = PackageImport::create(['source_type' => 'text', 'source_text' => str_repeat('Oferta Buenos Aires 4 noches. ', 10)]);

    (new ProcessPackageImport($import))->handle(
        new UrlExtractor,
        new TravelPackageExtractor($ai),
        new ImportPackageAction,
        new ImageSearchService([fakeImageProvider([candidate('unsplash:1'), candidate('pexels:2', ['provider' => 'Pexels'])])]),
    );

    $import->refresh();
    expect($import->status)->toBe(PackageImportStatus::ReadyForReview)
        ->and($import->travelPackage->is_active)->toBeFalse()
        ->and($import->image_candidates)->toHaveCount(2)
        ->and($import->lowConfidenceFields())->toBe(['price' => 'baja']);
});

test('the job offers the source images only when the user confirmed the rights', function () {
    $html = '<html><head><title>Oferta</title><meta property="og:image" content="https://93.184.216.34/hero.jpg"></head><body><main>'.str_repeat('Cinco noches en Bariloche con desayuno. ', 6).'</main></body></html>';
    Http::fake(['93.184.216.34/*' => Http::response($html, 200)]);

    $run = function (bool $rights) {
        $ai = Mockery::mock(AiConciergeService::class);
        $ai->shouldReceive('generate')->andReturn(aiJson());
        $import = PackageImport::create(['source_type' => 'url', 'source_url' => 'https://93.184.216.34/oferta', 'use_source_images' => $rights]);

        (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor($ai), new ImportPackageAction, new ImageSearchService([]));

        return collect($import->fresh()->image_candidates)->pluck('provider')->all();
    };

    expect($run(false))->toBe([])->and($run(true))->toBe(['Fuente original']);
});

test('a failing import ends as failed with the reason', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->andReturn('basura');
    $import = PackageImport::create(['source_type' => 'text', 'source_text' => 'texto']);

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor($ai), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::Failed)->and($import->fresh()->error)->toContain('no devolvió');
});

test('a pdf is sent to the ai as a file', function () {
    Storage::disk('local')->put('package-imports/oferta.pdf', '%PDF-1.4 fake');
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->withArgs(fn ($prompt, $pdf) => str_ends_with((string) $pdf, 'package-imports/oferta.pdf'))->andReturn(aiJson());
    $import = PackageImport::create(['source_type' => 'pdf', 'source_file' => 'package-imports/oferta.pdf']);

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor($ai), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::ReadyForReview);
});

// --- Image providers ---

test('unsplash, pexels and wikimedia results are normalized with their credits', function () {
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response(['results' => [['id' => 'u1', 'urls' => ['regular' => 'https://img/u1.jpg', 'small' => 'https://img/u1s.jpg'], 'user' => ['name' => 'Ana', 'links' => ['html' => 'https://unsplash.com/@ana']], 'links' => ['html' => 'https://unsplash.com/photos/u1', 'download_location' => 'https://api.unsplash.com/photos/u1/download']]]]),
        'api.pexels.com/v1/search*' => Http::response(['photos' => [['id' => 7, 'src' => ['large2x' => 'https://img/p7.jpg', 'medium' => 'https://img/p7m.jpg'], 'photographer' => 'Beto', 'photographer_url' => 'https://pexels.com/@beto', 'url' => 'https://pexels.com/photo/7']]]),
        'commons.wikimedia.org/*' => Http::response(['query' => ['pages' => [
            ['pageid' => 1, 'imageinfo' => [['mime' => 'image/jpeg', 'width' => 4000, 'thumburl' => 'https://upload/w1.jpg', 'url' => 'https://upload/o1.jpg', 'descriptionurl' => 'https://commons/File:1', 'extmetadata' => ['LicenseShortName' => ['value' => 'CC BY-SA 4.0'], 'Artist' => ['value' => '<a>Carla</a>']]]]],
            ['pageid' => 2, 'imageinfo' => [['mime' => 'image/jpeg', 'width' => 4000, 'thumburl' => 'https://upload/w2.jpg', 'extmetadata' => ['LicenseShortName' => ['value' => 'All rights reserved']]]]],
            ['pageid' => 3, 'imageinfo' => [['mime' => 'image/jpeg', 'width' => 300, 'thumburl' => 'https://upload/w3.jpg', 'extmetadata' => ['LicenseShortName' => ['value' => 'CC BY 4.0']]]]],
        ]]]),
    ]);

    $unsplash = (new UnsplashProvider('key'))->search('Obelisco')[0];
    $pexels = (new PexelsProvider('key'))->search('Obelisco')[0];
    $wiki = (new WikimediaProvider)->search('Obelisco');

    expect($unsplash)->toMatchArray(['provider' => 'Unsplash', 'author' => 'Ana', 'license' => 'Unsplash License'])
        ->and($pexels)->toMatchArray(['provider' => 'Pexels', 'author' => 'Beto'])
        ->and($wiki)->toHaveCount(1)
        ->and($wiki[0])->toMatchArray(['provider' => 'Wikimedia Commons', 'author' => 'Carla', 'license' => 'CC BY-SA 4.0']);
});

test('providers without a key are skipped and wikimedia still works', function () {
    expect((new UnsplashProvider(null))->isConfigured())->toBeFalse()
        ->and((new PexelsProvider(''))->isConfigured())->toBeFalse()
        ->and((new WikimediaProvider)->isConfigured())->toBeTrue();
});

test('a failing provider does not break the search', function () {
    $broken = new class implements ImageProvider
    {
        public function isConfigured(): bool
        {
            return true;
        }

        public function search(string $query, int $limit = 6): array
        {
            throw new RuntimeException('boom');
        }
    };

    $found = (new ImageSearchService([$broken, fakeImageProvider([candidate()])]))->search(['a']);

    expect($found)->toHaveCount(1);
});

test('a chosen photo is downloaded as webp with its credit and unsplash is notified', function () {
    Http::fake([
        '93.184.216.34/*' => Http::response(pngBytes(), 200, ['Content-Type' => 'image/png']),
        'api.unsplash.com/*' => Http::response('', 200),
    ]);

    $result = app(ImageDownloader::class)->store(candidate());

    Storage::disk('uploads')->assertExists($result['path']);
    expect($result['path'])->toEndWith('.webp')
        ->and($result['credit'])->toMatchArray(['author' => 'Juan Pérez', 'provider' => 'Unsplash', 'license' => 'Unsplash License']);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/photos/1/download'));
});

test('downloading a photo from an internal address is refused', function () {
    app(ImageDownloader::class)->store(candidate('x', ['url' => 'http://127.0.0.1/secret.png']));
})->throws(InvalidArgumentException::class);

// --- Review screen ---

function reviewableImport(): PackageImport
{
    $import = PackageImport::create(['source_type' => 'text', 'source_text' => 'x', 'status' => PackageImportStatus::ReadyForReview, 'image_candidates' => [candidate('unsplash:1'), candidate('pexels:2', ['provider' => 'Pexels', 'author' => 'Beto'])]]);
    $payload = (new TravelPackageExtractor(Mockery::mock(AiConciergeService::class)))->normalize(json_decode(aiJson(), true));
    app(ImportPackageAction::class)->createDraft($import, $payload);

    return $import->fresh();
}

test('the chosen images become the cover and gallery with their credits', function () {
    Http::fake(['93.184.216.34/*' => Http::response(pngBytes(), 200), 'api.unsplash.com/*' => Http::response('', 200)]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $import = reviewableImport();

    Livewire::test(ReviewPackageImport::class, ['record' => $import])
        ->set('selected', ['unsplash:1', 'pexels:2'])
        ->set('cover', 'pexels:2')
        ->call('applySelectedImages');

    $package = $import->travelPackage->fresh();
    expect($package->cover_image)->toEndWith('.webp')
        ->and($package->gallery)->toHaveCount(1)
        ->and($package->image_credits)->toHaveCount(2)
        ->and(collect($package->image_credits)->pluck('author')->all())->toContain('Juan Pérez', 'Beto')
        ->and($package->is_active)->toBeFalse();
});

test('a draft cannot be published without cover and price', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $import = reviewableImport();

    Livewire::test(ReviewPackageImport::class, ['record' => $import])->call('publish');

    expect($import->travelPackage->fresh()->is_active)->toBeFalse()->and($import->fresh()->status)->toBe(PackageImportStatus::ReadyForReview);
});

test('a complete draft can be published', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $import = reviewableImport();
    $import->travelPackage->update(['cover_image' => 'ideas/x.webp']);

    Livewire::test(ReviewPackageImport::class, ['record' => $import])->call('publish');

    expect($import->travelPackage->fresh()->is_active)->toBeTrue()->and($import->fresh()->status)->toBe(PackageImportStatus::Published);
});

test('discarding removes the draft but keeps the record', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $import = reviewableImport();
    $packageId = $import->travel_package_id;

    Livewire::test(ReviewPackageImport::class, ['record' => $import])->call('discard');

    expect(TravelPackage::find($packageId))->toBeNull()->and($import->fresh()->status)->toBe(PackageImportStatus::Discarded);
});

// --- PDF with providers that cannot read PDFs ---

test('with Groq the job sends the text of the PDF instead of the file', function () {
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => AiProvider::Groq, 'ai_api_key' => 'gsk_key']);
    Storage::fake('local');
    Storage::disk('local')->put('imports/oferta.pdf', Pdf::loadHTML('<p>Oferta Colombia: Bogotá, Pereira y Medellín, 9 noches, salida 10 de mayo.</p>')->output());
    $import = PackageImport::create(['source_type' => 'pdf', 'source_file' => 'imports/oferta.pdf']);

    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->withArgs(fn (string $prompt, $pdfPath) => $pdfPath === null && str_contains($prompt, 'Bogotá, Pereira y Medellín'))->andReturn(aiJson());

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor($ai), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::ReadyForReview)
        ->and($import->fresh()->extracted_text)->toContain('Colombia');
});

test('with Gemini the PDF is still sent as a file', function () {
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => AiProvider::Gemini, 'ai_api_key' => 'key']);
    Storage::fake('local');
    Storage::disk('local')->put('imports/oferta.pdf', Pdf::loadHTML('<p>Oferta</p>')->output());
    $import = PackageImport::create(['source_type' => 'pdf', 'source_file' => 'imports/oferta.pdf']);

    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('generate')->once()->withArgs(fn (string $prompt, $pdfPath) => $pdfPath !== null && str_ends_with($pdfPath, 'oferta.pdf'))->andReturn(aiJson());

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor($ai), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::ReadyForReview);
});

test('a scanned PDF with Groq fails the import with a clear reason', function () {
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => AiProvider::Groq, 'ai_api_key' => 'gsk_key']);
    Storage::fake('local');
    Storage::disk('local')->put('imports/scan.pdf', Pdf::loadHTML('<div style="width:80px;height:80px;background:#ddd"></div>')->output());
    $import = PackageImport::create(['source_type' => 'pdf', 'source_file' => 'imports/scan.pdf']);

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor(Mockery::mock(AiConciergeService::class)), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::Failed)->and($import->fresh()->error)->toContain('imagen escaneada');
});

test('a page that loads its content with JavaScript is rejected with a clear reason', function () {
    Http::fake(['93.184.216.34/*' => Http::response('<html><head><title>Disney en familia</title></head><body><div id="app"></div><footer>ebooking 0810 888 6735 Empresa Nuestras redes</footer><script src="/app.js"></script></body></html>', 200)]);
    $import = PackageImport::create(['source_type' => 'url', 'source_url' => 'https://93.184.216.34/packages/details/?id=1']);

    (new ProcessPackageImport($import))->handle(new UrlExtractor, new TravelPackageExtractor(Mockery::mock(AiConciergeService::class)), new ImportPackageAction, new ImageSearchService([]));

    expect($import->fresh()->status)->toBe(PackageImportStatus::Failed)
        ->and($import->fresh()->error)->toContain('JavaScript')->toContain('Texto pegado');
});
