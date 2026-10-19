<?php

use App\Actions\Leads\CaptureLeadAction;
use App\Ai\Agents\ConciergeAgent;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Http\Resources\LeadResource;
use App\Jobs\ProcessAiResponse;
use App\Jobs\ProcessLeadExtraction;
use App\Mail\LeadReceivedMail;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use App\Services\AiConciergeService;
use App\Services\LeadEnrichmentService;
use App\Support\ChatThrottle;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

test('two anonymous leads create two different customers', function () {
    $action = app(CaptureLeadAction::class);

    $first = $action->execute(['customer_name' => 'Ana', 'customer_phone' => null, 'raw_message' => 'a']);
    $second = $action->execute(['customer_name' => 'Beto', 'customer_phone' => 'Sin teléfono', 'raw_message' => 'b']);
    $third = $action->execute(['customer_name' => 'Cris', 'customer_phone' => 'Web-Form', 'raw_message' => 'c']);

    expect(collect([$first, $second, $third])->pluck('customer_id')->unique())->toHaveCount(3)
        ->and($second->customer->phone)->toBeNull()
        ->and($first->customer_name)->toBe('Ana');
});

test('a returning visitor is matched by normalized phone', function () {
    $action = app(CaptureLeadAction::class);

    $first = $action->execute(['customer_name' => 'Ana', 'customer_phone' => '+54 9 11 5555-1234', 'raw_message' => 'a']);
    $again = $action->execute(['customer_name' => 'Ana', 'customer_phone' => '+5491155551234', 'raw_message' => 'b']);

    expect($again->customer_id)->toBe($first->customer_id);
});

test('phone numbers are normalized and placeholders rejected', function () {
    expect(PhoneNumber::normalize('+54 9 (11) 5555-1234'))->toBe('+5491155551234')
        ->and(PhoneNumber::normalize('011 5555 1234'))->toBe('01155551234')
        ->and(PhoneNumber::normalize('Sin teléfono'))->toBeNull()
        ->and(PhoneNumber::normalize('No provisto'))->toBeNull()
        ->and(PhoneNumber::normalize('123'))->toBeNull()
        ->and(PhoneNumber::normalize(null))->toBeNull();
});

test('the AI extraction saves all the extracted fields', function () {
    $lead = Lead::factory()->create(['customer_name' => 'Web Guest', 'ai_data' => []]);

    app(LeadEnrichmentService::class)->apply($lead, [
        'destino' => 'Bariloche', 'presupuesto' => 'USD 3000', 'pasajeros' => '2 adultos',
        'fecha' => 'segunda quincena de julio', 'noches' => 7, 'ciudad_salida' => 'Rosario',
        'nombre' => 'Lucía', 'resumen' => 'Bariloche en julio', 'requiere_atencion' => true,
    ]);

    $lead->refresh();
    expect($lead->ai_data)->toMatchArray([
        'destino' => 'Bariloche', 'presupuesto' => 'USD 3000', 'pasajeros' => '2 adultos',
        'fecha' => 'segunda quincena de julio', 'noches' => 7, 'ciudad_salida' => 'Rosario',
    ])
        ->and($lead->customer_name)->toBe('Lucía')
        ->and($lead->ai_summary)->toBe('Bariloche en julio')
        ->and($lead->needs_human_attention)->toBeTrue();
});

test('the API job extracts from the raw message and updates the lead', function () {
    $lead = Lead::factory()->create(['raw_message' => 'Quiero ir a Salta en agosto', 'ai_data' => []]);

    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractLeadData')->once()->with('Quiero ir a Salta en agosto')->andReturn(['destino' => 'Salta', 'fecha' => 'agosto']);

    (new ProcessAiResponse($lead))->handle($ai, new LeadEnrichmentService);

    expect($lead->fresh()->ai_data)->toMatchArray(['destino' => 'Salta', 'fecha' => 'agosto']);
});

test('the extraction job uses the same enrichment', function () {
    $lead = Lead::factory()->create(['ai_data' => []]);

    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractLeadData')->andReturn(['noches' => 5, 'ciudad_salida' => 'Córdoba']);
    app()->instance(AiConciergeService::class, $ai);

    (new ProcessLeadExtraction($lead, 'hola'))->handle($ai);

    expect($lead->fresh()->ai_data)->toMatchArray(['noches' => 5, 'ciudad_salida' => 'Córdoba']);
});

test('the public API returns only the id and status of the lead', function () {
    $response = $this->postJson('/api/leads', [
        'customer_name' => 'Ana', 'customer_phone' => '+5491155551234', 'raw_message' => 'Hola',
    ])->assertCreated();

    expect(array_keys($response->json('data')))->toBe(['id', 'status'])
        ->and(LeadResource::class)->toBeString();
});

test('the lead received mail is queued', function () {
    Mail::fake();

    app(CaptureLeadAction::class)->execute([
        'customer_name' => 'Ana', 'customer_email' => 'ana@example.com', 'raw_message' => 'Hola',
    ]);

    Mail::assertQueued(LeadReceivedMail::class);
});

test('a mail failure does not break saving the lead', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    $lead = app(CaptureLeadAction::class)->execute([
        'customer_name' => 'Ana', 'customer_email' => 'ana@example.com', 'raw_message' => 'Hola',
    ]);

    expect($lead->exists)->toBeTrue();
});

test('the chat is rate limited per visitor', function () {
    RateLimiter::clear('chat:'.sha1('1.1.1.1|abc'));

    for ($i = 0; $i < ChatThrottle::MESSAGES_PER_MINUTE; $i++) {
        expect(ChatThrottle::attempt('1.1.1.1', 'abc'))->toBeNull();
    }

    expect(ChatThrottle::attempt('1.1.1.1', 'abc'))->toContain('muy rápido')
        ->and(ChatThrottle::attempt('2.2.2.2', 'other'))->toBeNull();
});

test('the finance role sees every booking while sales only sees its own', function () {
    $sales = User::factory()->create(['role' => UserRole::Sales]);
    $finance = User::factory()->create(['role' => UserRole::Finances]);
    $other = User::factory()->create(['role' => UserRole::Sales]);

    $mine = Booking::factory()->create(['agent_id' => $sales->id, 'status' => BookingStatus::Senado]);
    $theirs = Booking::factory()->create(['agent_id' => $other->id, 'status' => BookingStatus::Senado]);

    $this->actingAs($finance);
    expect(BookingResource::getEloquentQuery()->pluck('id')->all())->toContain($mine->id, $theirs->id);

    $this->actingAs($sales);
    expect(BookingResource::getEloquentQuery()->pluck('id')->all())->toContain($mine->id)->not->toContain($theirs->id);
});

test('finance can view financial reports by default', function () {
    expect(User::factory()->create(['role' => UserRole::Finances])->hasPermission('view_financial_reports'))->toBeTrue()
        ->and(User::factory()->create(['role' => UserRole::Sales])->hasPermission('view_financial_reports'))->toBeFalse();
});

test('role cannot be mass assigned', function () {
    $user = new User;
    $user->fill(['name' => 'X', 'role' => UserRole::Admin]);

    expect($user->role)->toBeNull();
});

test('the generic assistant prompt has no hard coded agency names', function () {
    $lead = Lead::factory()->create();

    $prompt = (string) (new ConciergeAgent($lead))->instructions();

    expect($prompt)->not->toContain('Nela')->not->toContain('Belén')->not->toContain('Brisa')
        ->and($prompt)->toContain('nuestros asesores');
});

test('the split command separates leads that share a placeholder customer', function () {
    $shared = User::factory()->create(['role' => UserRole::Customer, 'phone' => 'Sin teléfono']);
    $a = Lead::factory()->create(['customer_id' => $shared->id, 'customer_name' => 'Ana']);
    $b = Lead::factory()->create(['customer_id' => $shared->id, 'customer_name' => 'Beto']);

    $this->artisan('omni:split-anonymous-customers')->assertSuccessful();
    expect($a->fresh()->customer_id)->toBe($shared->id);

    $this->artisan('omni:split-anonymous-customers --apply')->assertSuccessful();

    expect($a->fresh()->customer_id)->not->toBe($b->fresh()->customer_id)
        ->and($shared->fresh()->phone)->toBeNull();
});

test('the public API rejects oversized messages', function () {
    $this->postJson('/api/leads', [
        'customer_name' => 'Ana', 'customer_phone' => '+5491155551234', 'raw_message' => str_repeat('a', 2001),
    ])->assertUnprocessable()->assertJsonValidationErrors('raw_message');
});

test('the public API requires the token when one is configured', function () {
    config(['services.leads_api.token' => 'secret-token']);
    $payload = ['customer_name' => 'Ana', 'customer_phone' => '+5491155551234', 'raw_message' => 'Hola'];

    $this->postJson('/api/leads', $payload)->assertForbidden();
    $this->postJson('/api/leads', $payload, ['Authorization' => 'Bearer wrong'])->assertForbidden();
    $this->postJson('/api/leads', $payload, ['Authorization' => 'Bearer secret-token'])->assertCreated();
});

test('the public API is rate limited', function () {
    $payload = ['customer_name' => 'Ana', 'customer_phone' => '+5491155551234', 'raw_message' => 'Hola'];

    foreach (range(1, 10) as $ignored) {
        $this->postJson('/api/leads', $payload)->assertCreated();
    }

    $this->postJson('/api/leads', $payload)->assertTooManyRequests();
});

test('the public proposal page is rate limited', function () {
    foreach (range(1, 60) as $ignored) {
        $this->get('/presupuesto/unknown-token')->assertNotFound();
    }

    $this->get('/presupuesto/unknown-token')->assertTooManyRequests();
});
