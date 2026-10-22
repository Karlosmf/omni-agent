<?php

use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Ai\Agents\ConciergeAgent;
use App\Enums\BookingStatus;
use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Exceptions\MetaApiException;
use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Filament\Admin\Resources\ChannelAccounts\ChannelAccountResource;
use App\Filament\Admin\Resources\ChannelAccounts\Pages\ManageChannelAccounts;
use App\Filament\Admin\Resources\Leads\Pages\EditLead;
use App\Filament\Admin\Widgets\ChannelFunnelWidget;
use App\Jobs\ProcessIncomingSocialMessage;
use App\Jobs\ProcessIncomingWhatsApp;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\ChannelAccount;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\AiConciergeService;
use App\Services\Channels\InboundMessage;
use App\Services\Channels\MetaSocialGateway;
use App\Services\CustomerMergeService;
use App\Services\LeadBookingDraftService;
use App\Services\LeadEnrichmentService;
use App\Services\SlaService;
use App\Services\WhatsApp\CloudApiGateway;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

function metaPost(array $payload, string $secret): TestResponse
{
    $body = json_encode($payload);

    return test()->call('POST', '/webhooks/meta', [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, $secret),
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

function dm(string $object, string $accountId, array $message, string $sender = 'PSID1'): array
{
    return ['object' => $object, 'entry' => [['id' => $accountId, 'messaging' => [['sender' => ['id' => $sender], 'recipient' => ['id' => $accountId], 'message' => $message]]]]];
}

function socialInbound(Channel $channel, string $accountId, string $mid = 'm1', string $text = 'Hola, quiero viajar a Bariloche', string $sender = 'PSID1', ?array $referral = null): InboundMessage
{
    return new InboundMessage($channel, $accountId, $sender, $mid, $text, true, 'Marta', $referral);
}

function fakeGraph(): void
{
    $n = 0;
    Http::fake(['graph.example.test/*' => function () use (&$n) {
        return Http::response(['message_id' => 'mid.OUT'.++$n]);
    }]);
}

function fakeAi(string $reply = '¿Para qué fecha?'): void
{
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldReceive('execute')->andReturn($reply);
    app()->instance(ProcessChatbotInteractionAction::class, $action);
}

beforeEach(function () {
    Mail::fake();
    config(['services.whatsapp.graph_url' => 'https://graph.example.test', 'services.meta.app_secret' => null, 'services.meta.verify_token' => null]);
    $this->account = ChannelAccount::factory()->create(['channel' => Channel::Instagram, 'external_id' => 'IG1', 'app_secret' => 'ig-secret', 'verify_token' => 'ig-verify']);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('meta verifies the social webhook with a connected account token only', function () {
    $this->get('/webhooks/meta?hub_mode=subscribe&hub_verify_token=ig-verify&hub_challenge=777')->assertOk()->assertSee('777');
    $this->get('/webhooks/meta?hub_mode=subscribe&hub_verify_token=nope&hub_challenge=777')->assertForbidden();
});

test('a social post with a bad signature is rejected', function () {
    Queue::fake();

    metaPost(dm('instagram', 'IG1', ['mid' => 'm1', 'text' => 'hola']), 'wrong')->assertForbidden();

    Queue::assertNothingPushed();
});

test('a signed instagram message is queued', function () {
    Queue::fake();

    metaPost(dm('instagram', 'IG1', ['mid' => 'm1', 'text' => 'Hola']), 'ig-secret')->assertOk();

    Queue::assertPushed(ProcessIncomingSocialMessage::class, fn ($job) => $job->message->channel === Channel::Instagram
        && $job->message->senderId === 'PSID1' && $job->message->externalId === 'm1' && $job->message->accountExternalId === 'IG1');
});

test('echoes of our own replies and attachments without text are handled', function () {
    Queue::fake();

    metaPost(dm('instagram', 'IG1', ['mid' => 'echo', 'text' => 'respuesta', 'is_echo' => true]), 'ig-secret')->assertOk();
    metaPost(dm('instagram', 'IG1', ['mid' => 'img', 'attachments' => [['type' => 'image']]]), 'ig-secret')->assertOk();

    Queue::assertPushed(ProcessIncomingSocialMessage::class, 1);
    Queue::assertPushed(ProcessIncomingSocialMessage::class, fn ($job) => $job->message->text === '[Imagen]' && ! $job->message->isText);
});

test('delivery receipts update the status of our messages', function () {
    $lead = Lead::factory()->create();
    $message = $lead->messages()->create(['role' => 'assistant', 'content' => 'hola', 'channel' => 'instagram', 'direction' => 'out', 'external_id' => 'mid.OUT', 'status' => 'sent']);
    $payload = ['object' => 'page', 'entry' => [['id' => 'P1', 'messaging' => [['sender' => ['id' => 'x'], 'delivery' => ['mids' => ['mid.OUT']]]]]]];

    $messenger = ChannelAccount::factory()->channel(Channel::Messenger)->create(['external_id' => 'P1', 'app_secret' => 'pg-secret']);
    metaPost($payload, 'pg-secret')->assertOk();

    expect($message->fresh()->status)->toBe('delivered')->and($messenger->channel)->toBe(Channel::Messenger);
});

test('a first instagram message creates a lead without phone or email and the AI answers through meta', function () {
    fakeAi();
    fakeGraph();

    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1', referral: ['source' => 'ADS', 'ad_id' => '99']));

    expect($lead->channel)->toBe('instagram')
        ->and($lead->source)->toBe('instagram')
        ->and($lead->channel_user_id)->toBe('PSID1')
        ->and($lead->channel_account_id)->toBe($this->account->id)
        ->and($lead->customer->phone)->toBeNull()
        ->and($lead->customer_name)->toBe('Marta')
        ->and($lead->attribution)->toMatchArray(['ad_id' => '99'])
        ->and($lead->isInsideReplyWindow())->toBeTrue()
        ->and($lead->messages()->where('direction', 'out')->first())->toMatchArray(['channel' => 'instagram', 'status' => 'sent']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/IG1/messages')
        && $request['recipient']['id'] === 'PSID1' && $request['message']['text'] === '¿Para qué fecha?'
        && $request->hasHeader('Authorization', 'Bearer '.$this->account->access_token));
});

test('the same sender keeps one open lead and the same message is not processed twice', function () {
    fakeAi();
    fakeGraph();
    $service = app(WhatsAppConversationService::class);

    $first = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'm1'));
    $second = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'm2', 'otra cosa'));
    $duplicate = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'm2', 'otra cosa'));

    expect($second->id)->toBe($first->id)->and($duplicate)->toBeNull()->and(Lead::count())->toBe(1)->and(Message::where('direction', 'in')->count())->toBe(2);
});

test('a returning customer after a closed lead gets a new lead on the same customer', function () {
    fakeAi();
    fakeGraph();
    $service = app(WhatsAppConversationService::class);

    $old = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'm1'));
    $old->update(['status' => LeadStatus::Closed]);
    $new = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'm3'));

    expect($new->id)->not->toBe($old->id)->and($new->customer_id)->toBe($old->customer_id)->and(User::where('role', UserRole::Customer)->count())->toBe(1);
});

test('messages for unknown or disabled accounts are ignored', function () {
    fakeAi();
    $this->account->update(['is_active' => false]);

    $result = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    expect($result)->toBeNull()->and(Lead::count())->toBe(0);
});

test('an agent reply on instagram pauses the AI, records the first response and respects the window', function () {
    fakeAi();
    fakeGraph();
    $service = app(WhatsAppConversationService::class);
    $lead = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    $service->sendAgentReply($lead, $this->admin, 'Hola Marta, soy Ana');

    expect($lead->fresh())->ai_paused->toBeTrue()->first_response_at->not->toBeNull()
        ->and($lead->messages()->latest('id')->first())->toMatchArray(['sent_by_user_id' => $this->admin->id, 'status' => 'sent']);

    $lead->update(['last_inbound_at' => now()->subHours(30)]);

    expect(fn () => $service->sendAgentReply($lead->fresh(), $this->admin, 'tarde'))->toThrow(RuntimeException::class, '24 horas');
});

test('a failing meta send marks the AI reply as failed without breaking the conversation', function () {
    fakeAi();
    Http::fake(['graph.example.test/*' => Http::response(['error' => ['message' => 'token vencido']], 400)]);

    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    expect($lead->messages()->where('direction', 'out')->first()->status)->toBe('failed');
});

test('the handoff notification names the channel', function () {
    fakeAi('¡Perfecto! Ya tengo todo lo que necesito.');
    fakeGraph();

    app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    expect($this->admin->notifications()->first()->data['title'])->toStartWith('Instagram:');
});

test('templates cannot be sent on social channels', function () {
    fakeAi();
    fakeGraph();
    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));
    $template = WhatsAppTemplate::create(['purpose' => 'follow_up', 'name' => 'x', 'language' => 'es_AR', 'body' => 'Hola', 'variables_count' => 0]);

    expect(fn () => app(WhatsAppConversationService::class)->sendTemplate($lead, $template, [], $this->admin))->toThrow(RuntimeException::class, 'WhatsApp');
});

// --- Several WhatsApp numbers ---

test('a whatsapp message is attached to the connected number that received it and answered from it', function () {
    fakeAi();
    Http::fake(['graph.example.test/*' => Http::response(['messages' => [['id' => 'wamid.OUT']]])]);
    $number = ChannelAccount::factory()->channel(Channel::WhatsApp)->create(['external_id' => 'PN-VENTAS', 'access_token' => 'ventas-token']);
    app()->forgetInstance(WhatsAppGateway::class);
    app()->singleton(WhatsAppGateway::class, CloudApiGateway::class);

    $lead = app(WhatsAppConversationService::class)->receive(['id' => 'wamid.IN', 'from' => '5491155551234', 'type' => 'text', 'text' => ['body' => 'Hola']], 'Lucía', 'PN-VENTAS');

    expect($lead->channel)->toBe('whatsapp')->and($lead->channel_account_id)->toBe($number->id);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/PN-VENTAS/messages') && $request->hasHeader('Authorization', 'Bearer ventas-token'));
});

test('the whatsapp webhook passes the receiving number to the job', function () {
    Queue::fake();
    config(['services.whatsapp.app_secret' => 'app-secret']);
    $payload = ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
        'metadata' => ['phone_number_id' => 'PN-SOPORTE'],
        'messages' => [['id' => 'wamid.X', 'from' => '5491155551234', 'type' => 'text', 'text' => ['body' => 'hola']]],
    ]]]]]];
    $body = json_encode($payload);

    $this->call('POST', '/webhooks/whatsapp', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'app-secret'), 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    Queue::assertPushed(ProcessIncomingWhatsApp::class, fn ($job) => $job->phoneNumberId === 'PN-SOPORTE');
});

test('channel account secrets are stored encrypted', function () {
    $raw = DB::table('channel_accounts')->where('id', $this->account->id)->first();

    expect($raw->access_token)->not->toBe($this->account->access_token)->and($this->account->fresh()->access_token)->toBe($this->account->access_token);
});

// --- Admin screens ---

test('an admin connects an instagram account and the token is saved encrypted', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageChannelAccounts::class)
        ->callAction('create', [
            'channel' => 'instagram', 'label' => 'Instagram agencia', 'external_id' => '1789', 'access_token' => 'tok', 'app_secret' => 'sec', 'verify_token' => 'v', 'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    $account = ChannelAccount::where('external_id', '1789')->first();

    expect($account->access_token)->toBe('tok')->and($account->channel)->toBe(Channel::Instagram);
});

test('only admins see the channel accounts screen', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Customer]));

    expect(ChannelAccountResource::canAccess())->toBeFalse();
});

test('the inbox lists conversations of every channel and filters by channel and account', function () {
    fakeAi();
    fakeGraph();
    $service = app(WhatsAppConversationService::class);
    $other = ChannelAccount::factory()->channel(Channel::Messenger)->create(['external_id' => 'PG1']);
    $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'a1', 'hola', 'U1'));
    $service->receiveSocial(socialInbound(Channel::Messenger, 'PG1', 'b1', 'buenas', 'U2'));
    $this->actingAs($this->admin);

    $inbox = Livewire::test(WhatsAppInbox::class)->assertSee('Messenger');

    expect($inbox->instance()->conversations())->toHaveCount(2);

    $inbox->call('setChannel', 'instagram');
    expect($inbox->instance()->conversations()->pluck('channel')->all())->toBe(['instagram']);

    $inbox->call('setChannel', 'all')->set('account', $other->id);
    expect($inbox->instance()->conversations()->pluck('channel_account_id')->all())->toBe([$other->id]);
});

test('the inbox replies on instagram and warns when the window is closed', function () {
    fakeAi();
    fakeGraph();
    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));
    $this->actingAs($this->admin);

    Livewire::test(WhatsAppInbox::class)
        ->call('open', $lead->id)
        ->set('reply', 'Hola Marta')
        ->call('send')
        ->assertSet('reply', '');

    Http::assertSent(fn ($r) => ($r['message']['text'] ?? null) === 'Hola Marta');

    $lead->update(['last_inbound_at' => now()->subDays(2)]);

    Livewire::test(WhatsAppInbox::class)
        ->call('open', $lead->id)
        ->assertSee('no se puede responder')
        ->set('reply', 'tarde')
        ->call('send')
        ->assertNotified('Pasaron más de 24 horas');
});

// --- Booking draft from the conversation ---

function leadWithChat(): Lead
{
    $customer = User::factory()->create(['role' => UserRole::Customer, 'name' => 'Web Guest']);
    $lead = Lead::factory()->create(['customer_id' => $customer->id, 'customer_name' => 'Marta', 'ai_data' => ['destino' => 'Salta', 'pasajeros' => '2 adultos, 1 niño', 'noches' => 5]]);
    $lead->messages()->create(['role' => 'user', 'content' => 'Hola, somos Marta Gómez y queremos ir a Bariloche 7 noches']);

    return $lead;
}

test('the booking draft reads the whole chat and normalises the answer', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractBookingDraft')->andReturn([
        'destination' => 'Bariloche', 'travel_date' => now()->addMonths(4)->format('Y-m-d'), 'nights' => 7, 'adults' => 2,
        'children_ages' => [4, 9], 'budget_usd' => 3200, 'holder_name' => 'Marta Gómez', 'email' => 'no-es-email', 'notes' => 'Quieren hotel con pileta',
    ]);

    $draft = (new LeadBookingDraftService($ai))->draftFor(leadWithChat());

    expect($draft)->toMatchArray(['destination' => 'Bariloche', 'nights' => 7, 'adults' => 2, 'budget_usd' => 3200.0, 'holder_name' => 'Marta Gómez', 'email' => null, 'from_ai' => true])
        ->and($draft['children'])->toBe([['age' => 4], ['age' => 9]]);
});

test('without AI the draft falls back to what the lead already collected', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractBookingDraft')->andReturn([]);

    $draft = (new LeadBookingDraftService($ai))->draftFor(leadWithChat());

    expect($draft)->toMatchArray(['destination' => 'Salta', 'nights' => 5, 'adults' => 2, 'holder_name' => 'Marta', 'from_ai' => false])
        ->and($draft['children'])->toBe([['age' => 0]]);
});

test('past or malformed dates from the AI are discarded', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractBookingDraft')->andReturn(['travel_date' => '2020-01-01'], ['travel_date' => 'agosto']);
    $service = new LeadBookingDraftService($ai);

    expect($service->draftFor(leadWithChat())['travel_date'])->toBeNull()->and($service->draftFor(leadWithChat())['travel_date'])->toBeNull();
});

test('passenger text is parsed into adults and children', function (string $text, array $expected) {
    expect((new LeadBookingDraftService(Mockery::mock(AiConciergeService::class)))->parsePassengers($text))->toBe($expected);
})->with([
    ['4 adultos, 3 niños', [4, 3]],
    ['2 adultos', [2, 0]],
    ['3', [3, 0]],
    ['familia', [0, 0]],
]);

test('create booking from the conversation fills the form and creates a draft booking with the reviewed data', function () {
    $ai = Mockery::mock(AiConciergeService::class);
    $ai->shouldReceive('extractBookingDraft')->andReturn(['destination' => 'Bariloche', 'nights' => 7, 'adults' => 2, 'children_ages' => [6], 'holder_name' => 'Marta Gómez', 'notes' => 'Pileta', 'travel_date' => now()->addMonths(3)->format('Y-m-d')]);
    app()->instance(AiConciergeService::class, $ai);
    $lead = leadWithChat();
    $this->actingAs($this->admin);

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->mountAction('create_booking')
        ->assertSchemaStateSet(['holder_name' => 'Marta Gómez', 'destination' => 'Bariloche', 'nights' => 7, 'adults' => 2])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $booking = Booking::where('lead_id', $lead->id)->first();

    expect($booking)->holder_name->toBe('Marta Gómez')->destination->toBe('Bariloche')->nights->toBe(7)->passengers->toEqual(3)->internal_notes->toBe('Pileta')
        ->and($lead->fresh()->status)->toBe(LeadStatus::Closed);
});

// --- SLA ---

function waitingLead(array $overrides = []): Lead
{
    return Lead::factory()->create(array_merge([
        'channel' => 'instagram', 'source' => 'instagram', 'status' => LeadStatus::New, 'temperature' => LeadTemperature::Hot,
        'last_inbound_at' => now()->subMinutes(30), 'first_response_at' => null, 'sla_alerted_at' => null,
    ], $overrides));
}

test('a hot lead waiting past the SLA alerts the team once', function () {
    AgencySetting::factory()->create(['sla_first_response_minutes' => 15]);
    Cache::forget('agency_settings');
    $lead = waitingLead();

    $this->artisan('omni:check-sla')->expectsOutput('Leads avisados: 1')->assertSuccessful();
    $this->artisan('omni:check-sla')->expectsOutput('Leads avisados: 0')->assertSuccessful();

    expect($this->admin->notifications()->count())->toBe(1)->and($lead->fresh()->sla_alerted_at)->not->toBeNull();
});

test('the SLA ignores answered, cold, closed and recent leads or when it is switched off', function () {
    AgencySetting::factory()->create(['sla_first_response_minutes' => 15]);
    Cache::forget('agency_settings');
    waitingLead(['first_response_at' => now()->subMinutes(5)]);
    waitingLead(['temperature' => LeadTemperature::Cool, 'needs_human_attention' => false]);
    waitingLead(['status' => LeadStatus::Closed]);
    waitingLead(['last_inbound_at' => now()->subMinutes(2)]);

    expect(app(SlaService::class)->overdue())->toHaveCount(0);

    waitingLead();
    AgencySetting::query()->update(['sla_first_response_minutes' => null]);
    Cache::forget('agency_settings');

    expect(app(SlaService::class)->overdue())->toHaveCount(0);
});

// --- One customer across channels ---

test('a phone given in an instagram chat merges the anonymous customer into the one we already know', function () {
    $known = User::factory()->create(['role' => UserRole::Customer, 'name' => 'Marta Gómez', 'phone' => '+5491155551234']);
    $booking = Booking::factory()->create(['customer_id' => $known->id]);
    fakeAi();
    fakeGraph();
    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));
    $anonymous = $lead->customer;

    app(LeadEnrichmentService::class)->apply($lead, ['telefono' => '11 5555-1234', 'email' => null]);

    expect($lead->fresh()->customer_id)->toBe($known->id)
        ->and(User::find($anonymous->id))->toBeNull()
        ->and($booking->fresh()->customer_id)->toBe($known->id);
});

test('a new phone or email is simply saved on an anonymous customer', function () {
    fakeAi();
    fakeGraph();
    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    app(LeadEnrichmentService::class)->apply($lead, ['telefono' => '+5491166667777', 'email' => 'marta@example.com']);

    expect($lead->customer->fresh())->phone->toBe('+5491166667777')->email->toBe('marta@example.com');
});

test('a customer who already has contact data is never merged by what a chat claims', function () {
    $victim = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491155551234']);
    $mine = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491100000000', 'is_guest' => true]);
    $lead = Lead::factory()->create(['customer_id' => $mine->id]);

    app(CustomerMergeService::class)->adoptContact($lead, '+5491155551234', null);

    expect($lead->fresh()->customer_id)->toBe($mine->id)->and(User::find($victim->id))->not->toBeNull();
});

// --- Funnel ---

test('the channel funnel counts leads, quotes and confirmed bookings per channel', function () {
    $ig = waitingLead();
    $web = Lead::factory()->create(['source' => 'web_form', 'channel' => null]);
    Booking::factory()->create(['lead_id' => $ig->id, 'status' => BookingStatus::Senado]);
    Booking::factory()->create(['lead_id' => $ig->id, 'status' => BookingStatus::Presupuesto]);
    $this->actingAs($this->admin);

    $widget = new ChannelFunnelWidget;
    $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

    expect($data['labels'])->toBe(['Web', 'Instagram'])
        ->and($data['datasets'][0]['data'])->toBe([1, 1])
        ->and($data['datasets'][1]['data'])->toBe([0, 2])
        ->and($data['datasets'][2]['data'])->toBe([0, 1])
        ->and($web->conversationChannel())->toBe(Channel::Web);
});

// --- Hand-off by the AI's own qualification flag ---

test('finishing the qualification hands the conversation to a person without matching any phrase', function () {
    app()->forgetInstance(ProcessChatbotInteractionAction::class);
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => 'gemini', 'ai_api_key' => 'k']);
    ConciergeAgent::fake([conciergeTurn(['reply' => 'Listo, te contactan en breve.', 'calificacion_completa' => true])]);
    fakeGraph();

    $lead = app(WhatsAppConversationService::class)->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));

    expect($lead->fresh()->ai_paused)->toBeTrue()->and($this->admin->notifications()->first()->data['title'])->toStartWith('Instagram:');
});

test('a conversation that was already qualified does not hand off again on the next AI answer', function () {
    app()->forgetInstance(ProcessChatbotInteractionAction::class);
    Cache::forget('agency_settings');
    AgencySetting::factory()->create(['ai_provider' => 'gemini', 'ai_api_key' => 'k']);
    ConciergeAgent::fake([conciergeTurn(['calificacion_completa' => true]), conciergeTurn(['calificacion_completa' => true])]);
    fakeGraph();
    $service = app(WhatsAppConversationService::class);

    $lead = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'a1'));
    $service->resumeAi($lead);
    $lead->fresh()->update(['needs_human_attention' => false]);
    $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1', 'a2', 'una consulta más'));

    expect($this->admin->notifications()->count())->toBe(1)->and($lead->fresh()->ai_paused)->toBeFalse();
});

test('instagram login accounts send through graph.instagram.com with their own token', function () {
    config(['services.meta.instagram_graph_url' => 'https://ig.example.test', 'services.meta.graph_version' => 'v99.0']);
    Http::fake(['ig.example.test/*' => Http::response(['message_id' => 'mid.IG'])]);
    $account = ChannelAccount::factory()->create(['channel' => Channel::Instagram, 'external_id' => 'IG9', 'auth_type' => 'instagram_login', 'access_token' => 'igtoken']);

    expect(app(MetaSocialGateway::class)->sendText($account, 'U1', 'Hola'))->toBe('mid.IG');

    Http::assertSent(fn ($r) => $r->url() === 'https://ig.example.test/v99.0/IG9/messages' && $r->hasHeader('Authorization', 'Bearer igtoken'));
});

test('long replies are split by bytes and stripped of markdown', function () {
    $chunks = MetaSocialGateway::chunks(str_repeat('áéí ', 400), 1000);

    expect($chunks)->each(fn ($c) => expect(strlen($c->value))->toBeLessThanOrEqual(1000))
        ->and(count($chunks))->toBeGreaterThan(1)
        ->and(MetaSocialGateway::plainText("## Hola\n**Salta** y *Jujuy*"))->toBe("Hola\nSalta y Jujuy");

    Http::fake(['graph.example.test/*' => Http::response(['message_id' => 'm'])]);
    app(MetaSocialGateway::class)->sendText($this->account, 'U1', str_repeat('palabra ', 300));
    Http::assertSentCount(3);
});

test('an expired token pauses the account, warns the admins once and a new token reconnects it', function () {
    Http::fake(['graph.example.test/*' => Http::response(['error' => ['message' => 'Session expired', 'code' => 190]], 400)]);
    $gateway = app(MetaSocialGateway::class);

    expect(fn () => $gateway->sendText($this->account, 'U1', 'Hola'))->toThrow(MetaApiException::class);
    expect(fn () => $gateway->sendText($this->account->fresh(), 'U1', 'Hola'))->toThrow(MetaApiException::class, 'reconectarse');

    expect($this->account->fresh()->needsReconnect())->toBeTrue()->and($this->admin->notifications()->count())->toBe(1);
    Http::assertSentCount(1);

    $this->account->update(['access_token' => 'nuevo']);

    expect($this->account->fresh()->needsReconnect())->toBeFalse();
});

test('a server error is retried and never marks the account as disconnected', function () {
    Http::fake(['graph.example.test/*' => Http::sequence()->push('', 500)->push(['message_id' => 'ok'])]);

    expect(app(MetaSocialGateway::class)->sendText($this->account, 'U1', 'Hola'))->toBe('ok')
        ->and($this->account->fresh()->needsReconnect())->toBeFalse();
});

test('a person may answer after 24 hours with the human agent tag only when the account allows it', function () {
    fakeAi();
    fakeGraph();
    $service = app(WhatsAppConversationService::class);
    $lead = $service->receiveSocial(socialInbound(Channel::Instagram, 'IG1'));
    $lead->update(['last_inbound_at' => now()->subHours(30)]);

    expect(fn () => $service->sendAgentReply($lead->fresh(), $this->admin, 'tarde'))->toThrow(RuntimeException::class, '24 horas');

    $this->account->update(['allow_human_agent' => true]);
    $service->sendAgentReply($lead->fresh(), $this->admin, 'Perdón la demora');

    Http::assertSent(fn ($r) => ($r['tag'] ?? null) === 'HUMAN_AGENT' && $r['messaging_type'] === 'MESSAGE_TAG');

    $lead->update(['last_inbound_at' => now()->subDays(9)]);

    expect(fn () => $service->sendAgentReply($lead->fresh(), $this->admin, 'muy tarde'))->toThrow(RuntimeException::class);
});

test('with a webhook token configured only the secret url answers', function () {
    config(['services.meta.webhook_token' => 'abc123']);
    $url = fn (string $t = '') => '/webhooks/meta'.$t.'?hub_mode=subscribe&hub_verify_token=ig-verify&hub_challenge=77';

    $this->get($url())->assertForbidden();
    $this->get($url('/mal'))->assertForbidden();
    $this->get($url('/abc123'))->assertOk()->assertSee('77');
});
