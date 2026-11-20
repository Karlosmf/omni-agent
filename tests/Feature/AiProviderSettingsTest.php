<?php

use App\Ai\AgencyAi;
use App\Enums\AiProvider;
use App\Models\AgencySetting;
use App\Services\AiConciergeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function configureAi(AiProvider $provider, ?string $model = null): void
{
    AgencySetting::query()->delete();
    AgencySetting::create([
        'company_name' => 'Agencia',
        'contact_email' => 'info@agencia.test',
        'contact_phone' => '1155550000',
        'ai_provider' => $provider,
        'ai_api_key' => 'gsk_secret_key',
        'ai_model' => $model,
    ]);
}

it('stores the AI key encrypted', function () {
    configureAi(AiProvider::Groq);

    $raw = DB::table('agency_settings')->value('ai_api_key');

    expect($raw)->not->toBe('gsk_secret_key')
        ->and(AgencySetting::first()->ai_api_key)->toBe('gsk_secret_key');
});

it('calls Groq with its endpoint, the default model and the saved key', function () {
    configureAi(AiProvider::Groq);
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hola']]]])]);

    expect(app(AiConciergeService::class)->generate('Hola'))->toBe('Hola');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
        && $request['model'] === 'qwen/qwen3.8-27b'
        && $request->hasHeader('Authorization', 'Bearer gsk_secret_key'));
});

it('uses the model chosen by the agency', function () {
    configureAi(AiProvider::Groq, 'llama-3.1-8b-instant');
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]])]);

    app(AiConciergeService::class)->generate('Hola');

    Http::assertSent(fn ($request) => $request['model'] === 'llama-3.1-8b-instant');
});

it('refuses to read a PDF with a provider that cannot', function () {
    configureAi(AiProvider::Groq);
    Http::fake();

    expect(fn () => app(AiConciergeService::class)->generate('Leé esto', __FILE__))
        ->toThrow(Exception::class, 'no puede leer PDFs');

    Http::assertNothingSent();
});

it('reports a working connection', function () {
    configureAi(AiProvider::Groq);
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

    expect(app(AiConciergeService::class)->testConnection())->toBeNull();
});

it('reports the provider error when the connection fails', function () {
    configureAi(AiProvider::Groq);
    Http::fake(['api.groq.com/*' => Http::response('invalid key', 401)]);

    expect(app(AiConciergeService::class)->testConnection())->toContain('Groq');
});

it('asks for a JSON answer when the caller needs one', function () {
    configureAi(AiProvider::Groq);
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]])]);

    app(AiConciergeService::class)->generate('Importá esto', json: true);

    Http::assertSent(fn ($request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'objeto JSON válido'));
});

it('does not add the JSON instruction to a plain prompt', function () {
    configureAi(AiProvider::Groq);
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hola']]]])]);

    app(AiConciergeService::class)->generate('Hola');

    Http::assertSent(fn ($request) => ! str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'objeto JSON válido'));
});

it('builds one provider per saved key without touching config/ai.php', function () {
    configureAi(AiProvider::Groq);

    [$name, $model] = app(AgencyAi::class)->resolve();
    [$again] = app(AgencyAi::class)->resolve();

    expect($name)->toStartWith('agency_')->and($again)->toBe($name)->and($model)->toBe('qwen/qwen3.8-27b')
        ->and(config('ai.providers.'.$name))->toBeNull();
});

it('says the key is missing before calling any provider', function () {
    AgencySetting::query()->delete();
    AgencySetting::create(['company_name' => 'Agencia', 'contact_email' => 'info@agencia.test', 'contact_phone' => '1155550000', 'ai_provider' => AiProvider::Groq]);
    Http::fake();

    expect(app(AiConciergeService::class)->testConnection())->toBe('Falta cargar la API key.');

    Http::assertNothingSent();
});
