<?php

use App\Ai\Agents\BookingDraftAgent;
use App\Ai\Agents\ItineraryAgent;
use App\Ai\Agents\LeadExtractionAgent;
use App\Ai\Agents\TextAgent;
use App\Enums\AiProvider;
use App\Filament\Admin\Widgets\AiUsageWidget;
use App\Jobs\ProcessAiResponse;
use App\Jobs\ProcessLeadExtraction;
use App\Models\AgencySetting;
use App\Models\Lead;
use App\Models\Message;
use App\Services\AiConciergeService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => AiProvider::Gemini, 'ai_api_key' => 'key']);
});

test('the lead data is extracted with a structured answer and keeps the old keys', function () {
    LeadExtractionAgent::fake([[
        'destino' => 'Bariloche', 'presupuesto' => 'USD 3000', 'pasajeros' => '2 adultos', 'fecha' => 'julio', 'noches' => 7,
        'ciudad_salida' => 'Rosario', 'nombre' => 'Ana', 'telefono' => null, 'email' => null, 'resumen' => 'Bariloche en julio', 'requiere_atencion' => false,
    ]])->preventStrayPrompts();

    $data = app(AiConciergeService::class)->extractLeadData('Hola soy Ana, queremos ir a Bariloche en julio');

    expect($data)->toMatchArray(['destino' => 'Bariloche', 'noches' => 7, 'nombre' => 'Ana', 'requiere_atencion' => false]);
    LeadExtractionAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, '<texto>') && str_contains($prompt->prompt, 'Bariloche'));
});

test('a failing extraction returns nothing instead of breaking the caller', function () {
    LeadExtractionAgent::fake(fn () => throw new Exception('boom'));

    expect(app(AiConciergeService::class)->extractLeadData('Hola'))->toBe([]);
});

test('the booking draft reads the transcript and an empty chat costs nothing', function () {
    BookingDraftAgent::fake([[
        'destination' => 'Salta', 'travel_date' => '2027-01-10', 'nights' => 5, 'adults' => 2, 'children_ages' => [4], 'budget_usd' => 2500.0,
        'departure_city' => null, 'holder_name' => 'Marta Gómez', 'phone' => null, 'email' => null, 'notes' => null,
    ]])->preventStrayPrompts();
    $lead = Lead::factory()->create();

    expect(app(AiConciergeService::class)->extractBookingDraft($lead))->toBe([]);

    $lead->messages()->create(['role' => 'user', 'content' => 'Somos Marta Gómez, 2 adultos y un nene de 4']);
    $draft = app(AiConciergeService::class)->extractBookingDraft($lead);

    expect($draft)->toMatchArray(['destination' => 'Salta', 'adults' => 2, 'children_ages' => [4]]);
    BookingDraftAgent::assertPromptedTimes(1);
});

test('the itinerary comes back as a list of days', function () {
    ItineraryAgent::fake([['days' => [
        ['day' => 1, 'title' => 'Llegada', 'description' => 'Traslado al hotel'],
        ['day' => 2, 'title' => 'City tour', 'description' => 'Recorrido por la ciudad'],
    ]]]);

    $days = app(AiConciergeService::class)->generateItinerary('3 días en Salta');

    expect($days)->toHaveCount(2)->and($days[1]['title'])->toBe('City tour');
});

test('a failing itinerary returns an empty list', function () {
    ItineraryAgent::fake(fn () => throw new Exception('boom'));

    expect(app(AiConciergeService::class)->generateItinerary('3 días en Salta'))->toBe([]);
});

test('one-off prompts return the plain text of the model', function () {
    TextAgent::fake(['{"title":"Salta"}']);

    expect(app(AiConciergeService::class)->generate('Importá esto', json: true))->toBe('{"title":"Salta"}');
    TextAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'objeto JSON válido'));
});

test('the AI jobs retry with a backoff', function () {
    expect((new ProcessAiResponse(Lead::factory()->create()))->tries)->toBe(3)
        ->and((new ProcessAiResponse(Lead::factory()->create()))->backoff())->toBe([10, 60])
        ->and((new ProcessLeadExtraction(Lead::factory()->create(), 'hola'))->tries)->toBe(3);
});

test('the usage widget counts the month AI answers and tokens', function () {
    $lead = Lead::factory()->create();
    $lead->messages()->create(['role' => 'assistant', 'content' => 'a', 'input_tokens' => 1000, 'output_tokens' => 200]);
    $lead->messages()->create(['role' => 'assistant', 'content' => 'b', 'input_tokens' => 800, 'output_tokens' => 100]);
    $lead->messages()->create(['role' => 'assistant', 'content' => 'humano, sin tokens']);
    Message::query()->where('content', 'a')->update(['created_at' => now()->subMonths(2)]);
    AgencySetting::query()->update(['ai_monthly_message_limit' => 10]);
    Cache::forget('agency_settings');

    $widget = new AiUsageWidget;
    $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);

    expect($stats[0]->getValue())->toBe('1')->and($stats[0]->getDescription())->toContain('10% usado')
        ->and($stats[1]->getValue())->toBe('900');
});
