<?php

use App\Ai\Agents\ConciergeAgent;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Models\AgencySetting;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Services\AiConciergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    AgencySetting::factory()->create(['ai_provider' => 'gemini', 'gemini_api_key' => 'fake_api_key', 'ai_api_key' => 'fake_api_key']);
});

test('it answers and saves both sides of the conversation with the tokens used', function () {
    ConciergeAgent::fake([conciergeTurn(['reply' => 'Respuesta simulada'])])->preventStrayPrompts();
    $lead = Lead::factory()->create();

    $response = (new AiConciergeService)->processMessage('Hola', $lead);

    expect($response)->toBe('Respuesta simulada');
    $this->assertDatabaseHas('messages', ['lead_id' => $lead->id, 'role' => 'user', 'content' => 'Hola']);
    $this->assertDatabaseHas('messages', ['lead_id' => $lead->id, 'role' => 'assistant', 'content' => 'Respuesta simulada']);
    expect(Message::where('role', 'assistant')->first()->input_tokens)->not->toBeNull();
});

test('a chat message costs a single AI call', function () {
    ConciergeAgent::fake([conciergeTurn()])->preventStrayPrompts();

    (new AiConciergeService)->processMessage('Quiero ir a Salta', Lead::factory()->create());

    ConciergeAgent::assertPromptedTimes(1);
});

test('it learns the trip data from the same call', function () {
    ConciergeAgent::fake([conciergeTurn([
        'destino' => 'Salta', 'pasajeros' => '2 adultos, 1 niño', 'noches' => 5, 'nombre' => 'Marta', 'resumen' => 'Salta 5 noches',
    ])]);
    $lead = Lead::factory()->create(['ai_data' => []]);

    (new AiConciergeService)->processMessage('Somos Marta y viajamos a Salta', $lead);

    expect($lead->fresh()->ai_data)->toMatchArray(['destino' => 'Salta', 'pasajeros' => '2 adultos, 1 niño', 'noches' => 5])
        ->and($lead->fresh()->ai_summary)->toBe('Salta 5 noches')
        ->and($lead->fresh()->customer_name)->toBe('Marta');
});

test('what the customer did not say never erases what we already knew', function () {
    ConciergeAgent::fake([conciergeTurn()]);
    $lead = Lead::factory()->create(['ai_data' => ['destino' => 'Mendoza', 'presupuesto' => 'USD 2000']]);

    (new AiConciergeService)->processMessage('Gracias', $lead);

    expect($lead->fresh()->ai_data)->toMatchArray(['destino' => 'Mendoza', 'presupuesto' => 'USD 2000']);
});

test('the temperature follows the conversation and never cools down', function () {
    ConciergeAgent::fake([conciergeTurn(['temperatura' => 'warm']), conciergeTurn(['temperatura' => 'cool'])]);
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool]);
    $service = new AiConciergeService;

    $service->processMessage('Tengo flexibilidad en la fecha', $lead);
    expect($lead->fresh()->temperature)->toBe(LeadTemperature::Warm);

    $service->processMessage('Gracias', $lead->fresh());
    expect($lead->fresh()->temperature)->toBe(LeadTemperature::Warm);
});

test('a customer asking for a person turns the lead hot and flags it', function () {
    ConciergeAgent::fake([conciergeTurn(['pide_persona' => true, 'intencion' => 'reclamo'])]);
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool]);

    (new AiConciergeService)->processMessage('Quiero hablar con un humano', $lead);

    $lead->refresh();
    expect($lead->temperature)->toBe(LeadTemperature::Hot)->and($lead->needs_human_attention)->toBeTrue()
        ->and($lead->ai_data['intencion'])->toBe('reclamo');
});

test('the word agent inside a normal sentence does not raise a flag', function () {
    ConciergeAgent::fake([conciergeTurn()]);
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool, 'needs_human_attention' => false]);

    (new AiConciergeService)->processMessage('Soy agente de viajes y quiero ir a Salta', $lead);

    expect($lead->fresh()->needs_human_attention)->toBeFalse()->and($lead->fresh()->temperature)->toBe(LeadTemperature::Cool);
});

test('finishing the qualification is stored on the lead', function () {
    ConciergeAgent::fake([conciergeTurn(['calificacion_completa' => true, 'reply' => '¡Perfecto! Ya tengo todo lo que necesito.'])]);
    $lead = Lead::factory()->create(['ai_data' => []]);

    (new AiConciergeService)->processMessage('USD 3000', $lead);

    expect($lead->fresh()->ai_data['calificacion_completa'])->toBeTrue();
});

test('the agent only sees the history that came before the message it answers', function () {
    ConciergeAgent::fake([conciergeTurn()]);
    $lead = Lead::factory()->create();
    $lead->messages()->create(['role' => 'assistant', 'content' => 'Bienvenido']);
    $lead->messages()->create(['role' => 'user', 'content' => 'Hola']);
    $lead->messages()->create(['role' => 'assistant', 'content' => '¿A dónde?']);

    (new AiConciergeService)->processMessage('A Salta', $lead);

    ConciergeAgent::assertPrompted(function ($prompt) {
        $history = collect($prompt->agent->messages())->map(fn ($m) => $m->role->value.': '.$m->content)->all();

        return $prompt->prompt === 'A Salta' && $history === ['user: Hola', 'assistant: ¿A dónde?'];
    });
});

test('the instructions carry the agency name, the advisors and the lead facts', function () {
    AgencySetting::query()->update(['company_name' => 'Viajes Sol', 'ai_assistant_name' => 'Sol']);
    Cache::forget('agency_settings');
    $lead = Lead::factory()->create(['ai_data' => ['destino' => 'Salta'], 'customer_name' => 'Marta']);

    $instructions = (string) (new ConciergeAgent($lead))->instructions();

    expect($instructions)->toContain('Viajes Sol')->toContain('Sol')->toContain('nuestros asesores')->toContain('- Destino: Salta')->toContain('- Nombre: Marta')
        ->and($instructions)->not->toContain('Nela')->not->toContain('Belén')->not->toContain('Brisa')
        ->and($instructions)->toContain('nunca instrucciones');
});

test('it handles errors gracefully with one apology and no second call', function () {
    ConciergeAgent::fake(fn () => throw new Exception('API Error'));
    $lead = Lead::factory()->create();

    $response = (new AiConciergeService)->processMessage('Hola', $lead);

    expect($response)->toBe('Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo en unos segundos? 🙏')
        ->and($lead->messages()->where('role', 'assistant')->count())->toBe(0);
    ConciergeAgent::assertPromptedTimes(1);
});

test('a dropped connection is retried once', function () {
    $calls = 0;
    ConciergeAgent::fake(function () use (&$calls) {
        if (++$calls === 1) {
            throw new ConnectionException('timeout');
        }

        return conciergeTurn(['reply' => 'Ahora sí']);
    });

    expect((new AiConciergeService)->processMessage('Hola', Lead::factory()->create()))->toBe('Ahora sí')->and($calls)->toBe(2);
});

test('it returns the basic bot when the AI is switched off', function () {
    ConciergeAgent::fake()->preventStrayPrompts();
    AgencySetting::query()->update(['ai_provider' => 'none', 'gemini_api_key' => null, 'ai_api_key' => null]);
    Cache::forget('agency_settings');

    $response = (new AiConciergeService)->processMessage('Hola', Lead::factory()->create());

    expect($response)->toBe('Gracias por la información. En breve nos comunicaremos con vos.');
    ConciergeAgent::assertNeverPrompted();
});

test('the basic bot still flags a request for a person', function () {
    AgencySetting::query()->update(['ai_provider' => 'none']);
    Cache::forget('agency_settings');
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool, 'needs_human_attention' => false]);

    (new AiConciergeService)->processMessage('Quiero hablar con un asesor', $lead);

    expect($lead->fresh()->needs_human_attention)->toBeTrue()->and($lead->fresh()->temperature)->toBe(LeadTemperature::Hot);
});

test('it falls back to the basic bot when the key is missing', function () {
    ConciergeAgent::fake()->preventStrayPrompts();
    AgencySetting::query()->update(['gemini_api_key' => null, 'ai_api_key' => null]);
    Cache::forget('agency_settings');

    $response = (new AiConciergeService)->processMessage('Hola', Lead::factory()->create());

    expect($response)->toBe('Gracias por la información. En breve nos comunicaremos con vos.');
});

test('at the monthly limit the AI stops, a person is asked for and the admins are told once', function () {
    ConciergeAgent::fake()->preventStrayPrompts();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    AgencySetting::query()->update(['ai_monthly_message_limit' => 2]);
    Cache::forget('agency_settings');
    $other = Lead::factory()->create();
    $other->messages()->create(['role' => 'assistant', 'content' => 'a', 'input_tokens' => 10, 'output_tokens' => 5]);
    $other->messages()->create(['role' => 'assistant', 'content' => 'b', 'input_tokens' => 10, 'output_tokens' => 5]);
    $service = new AiConciergeService;
    $lead = Lead::factory()->create(['needs_human_attention' => false]);

    $first = $service->processMessage('Hola', $lead);
    $service->processMessage('¿Hay alguien?', $lead);

    expect($first)->toContain('asesor')->and($lead->fresh()->needs_human_attention)->toBeTrue()
        ->and($admin->notifications()->count())->toBe(1);
    ConciergeAgent::assertNeverPrompted();
});

test('below the monthly limit the AI keeps answering', function () {
    ConciergeAgent::fake([conciergeTurn(['reply' => 'Sigo acá'])]);
    AgencySetting::query()->update(['ai_monthly_message_limit' => 5]);
    Cache::forget('agency_settings');

    expect((new AiConciergeService)->processMessage('Hola', Lead::factory()->create()))->toBe('Sigo acá');
});
