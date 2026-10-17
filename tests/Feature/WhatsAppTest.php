<?php

use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Jobs\ProcessIncomingWhatsApp;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PaymentInstallment;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\CloudApiGateway;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

class FakeWhatsAppGateway implements WhatsAppGateway
{
    /** @var list<array<string, mixed>> */
    public array $sent = [];

    public bool $configured = true;

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function sendText(string $to, string $text): string
    {
        $this->sent[] = ['type' => 'text', 'to' => $to, 'text' => $text];

        return 'wamid.OUT'.count($this->sent);
    }

    public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string
    {
        $this->sent[] = ['type' => 'template', 'to' => $to, 'template' => $template->name, 'variables' => $variables];

        return 'wamid.OUT'.count($this->sent);
    }
}

function inboundText(string $text = 'Hola, quiero viajar a Salta', string $id = 'wamid.IN1', string $from = '5491155551234'): array
{
    return ['id' => $id, 'from' => $from, 'type' => 'text', 'text' => ['body' => $text]];
}

function signed(array $payload, string $secret = 'app-secret'): array
{
    $body = json_encode($payload);

    return [$body, ['X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, $secret), 'Content-Type' => 'application/json']];
}

function webhookPayload(array $value): array
{
    return ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => $value]]]]];
}

beforeEach(function () {
    Mail::fake();
    config([
        'services.whatsapp.app_secret' => 'app-secret',
        'services.whatsapp.verify_token' => 'verify-me',
        'services.whatsapp.phone_number_id' => '123',
        'services.whatsapp.access_token' => 'token',
    ]);
    $this->gateway = new FakeWhatsAppGateway;
    app()->instance(WhatsAppGateway::class, $this->gateway);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

// --- Phones ---

test('argentine numbers are unified to the whatsapp e164 format', function (string $input) {
    expect(PhoneNumber::toE164($input))->toBe('+5491155551234');
})->with([
    'e164' => '+5491155551234',
    'wa_id' => '5491155551234',
    'without the 9' => '541155551234',
    'spaced' => '+54 9 11 5555-1234',
    'with 0 and 15' => '011 15 5555 1234',
    'national' => '11 5555 1234',
]);

test('lookup variants cover the formats an existing customer may have', function () {
    expect(PhoneNumber::lookupVariants('+5491155551234'))->toContain('+5491155551234', '5491155551234', '1155551234', '01155551234');
});

// --- Webhook ---

test('meta can verify the webhook with the right token only', function () {
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=1234')->assertOk()->assertSee('1234');
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=1234')->assertForbidden();
});

test('a post with an invalid signature is rejected', function () {
    Queue::fake();
    [$body] = signed(webhookPayload(['messages' => [inboundText()]]));

    $this->call('POST', '/webhooks/whatsapp', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef', 'CONTENT_TYPE' => 'application/json'], $body)->assertForbidden();

    Queue::assertNothingPushed();
});

test('a post without configured secret is rejected', function () {
    Queue::fake();
    config(['services.whatsapp.app_secret' => '']);
    [$body, $headers] = signed(webhookPayload(['messages' => [inboundText()]]), '');

    $this->call('POST', '/webhooks/whatsapp', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => $headers['X-Hub-Signature-256'], 'CONTENT_TYPE' => 'application/json'], $body)->assertForbidden();
});

test('a signed message is queued with the customer profile name', function () {
    Queue::fake();
    [$body, $headers] = signed(webhookPayload(['contacts' => [['wa_id' => '5491155551234', 'profile' => ['name' => 'Lucía']]], 'messages' => [inboundText()]]));

    $this->call('POST', '/webhooks/whatsapp', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => $headers['X-Hub-Signature-256'], 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    Queue::assertPushed(ProcessIncomingWhatsApp::class, fn ($job) => $job->profileName === 'Lucía' && $job->message['id'] === 'wamid.IN1');
});

test('delivery statuses update the sent message', function () {
    Queue::fake();
    $lead = Lead::factory()->create();
    $message = $lead->messages()->create(['role' => 'assistant', 'content' => 'hola', 'channel' => 'whatsapp', 'direction' => 'out', 'external_id' => 'wamid.OUT9', 'status' => 'sent']);
    [$body, $headers] = signed(webhookPayload(['statuses' => [['id' => 'wamid.OUT9', 'status' => 'read']]]));

    $this->call('POST', '/webhooks/whatsapp', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => $headers['X-Hub-Signature-256'], 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($message->fresh()->status)->toBe('read');
});

// --- Conversations ---

function mockAi(string $reply = '¿Para qué fecha viajan?'): void
{
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldReceive('execute')->andReturn($reply);
    app()->instance(ProcessChatbotInteractionAction::class, $action);
}

test('an incoming message creates the customer and a whatsapp lead and the AI answers', function () {
    mockAi();

    $lead = app(WhatsAppConversationService::class)->receive(inboundText(), 'Lucía');

    expect($lead->source)->toBe('whatsapp')
        ->and($lead->customer->phone)->toBe('+5491155551234')
        ->and($lead->customer_name)->toBe('Lucía')
        ->and($lead->unread_count)->toBe(1)
        ->and($lead->isInsideWhatsAppWindow())->toBeTrue()
        ->and($lead->messages()->first())->toMatchArray(['channel' => 'whatsapp', 'direction' => 'in', 'external_id' => 'wamid.IN1'])
        ->and($this->gateway->sent)->toHaveCount(1)
        ->and($this->gateway->sent[0])->toMatchArray(['type' => 'text', 'to' => '+5491155551234', 'text' => '¿Para qué fecha viajan?']);
});

test('the same webhook delivered twice is processed once', function () {
    mockAi();
    $service = app(WhatsAppConversationService::class);

    $service->receive(inboundText());
    $second = $service->receive(inboundText());

    expect($second)->toBeNull()->and(Message::where('external_id', 'wamid.IN1')->count())->toBe(1)->and($this->gateway->sent)->toHaveCount(1);
});

test('a returning customer keeps one lead even if the number was stored in another format', function () {
    mockAi();
    $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '01155551234']);
    $lead = Lead::factory()->create(['customer_id' => $customer->id, 'source' => 'whatsapp', 'status' => LeadStatus::New]);

    $received = app(WhatsAppConversationService::class)->receive(inboundText());

    expect($received->id)->toBe($lead->id)->and(Lead::count())->toBe(1);
});

test('a closed lead is not reopened: a new one starts', function () {
    mockAi();
    $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491155551234']);
    $old = Lead::factory()->create(['customer_id' => $customer->id, 'source' => 'whatsapp', 'status' => LeadStatus::Closed]);

    $received = app(WhatsAppConversationService::class)->receive(inboundText());

    expect($received->id)->not->toBe($old->id)->and($received->customer_id)->toBe($customer->id);
});

test('the AI stays silent while a person is attending the conversation', function () {
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldNotReceive('execute');
    app()->instance(ProcessChatbotInteractionAction::class, $action);

    $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491155551234']);
    Lead::factory()->create(['customer_id' => $customer->id, 'source' => 'whatsapp', 'status' => LeadStatus::Contacted, 'ai_paused' => true]);

    app(WhatsAppConversationService::class)->receive(inboundText());

    expect($this->gateway->sent)->toBe([]);
});

test('media messages are stored but not answered by the AI', function () {
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldNotReceive('execute');
    app()->instance(ProcessChatbotInteractionAction::class, $action);

    $lead = app(WhatsAppConversationService::class)->receive(['id' => 'wamid.IMG', 'from' => '5491155551234', 'type' => 'image', 'image' => ['caption' => 'Mirá este hotel']]);

    expect($lead->messages()->first()->content)->toBe('[Imagen] Mirá este hotel')->and($this->gateway->sent)->toBe([]);
});

test('when the AI closes the qualification it stops answering and the team is notified', function () {
    mockAi('¡Perfecto! Ya tengo todo lo que necesito. Nuestros asesores se van a comunicar con vos. 😉');

    $lead = app(WhatsAppConversationService::class)->receive(inboundText());

    expect($lead->fresh()->ai_paused)->toBeTrue()
        ->and($lead->fresh()->needs_human_attention)->toBeTrue()
        ->and($this->admin->notifications()->count())->toBe(1);
});

test('a customer asking for a person pauses the AI', function () {
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldReceive('execute')->andReturnUsing(function ($text, Lead $lead) {
        $lead->update(['needs_human_attention' => true]);

        return 'Ya te paso con una persona.';
    });
    app()->instance(ProcessChatbotInteractionAction::class, $action);

    $lead = app(WhatsAppConversationService::class)->receive(inboundText('quiero hablar con un asesor'));

    expect($lead->fresh()->ai_paused)->toBeTrue();
});

test('AI replies are rate limited per conversation', function () {
    $action = Mockery::mock(ProcessChatbotInteractionAction::class);
    $action->shouldReceive('execute')->times(WhatsAppConversationService::AI_REPLIES_PER_MINUTE)->andReturn('ok');
    app()->instance(ProcessChatbotInteractionAction::class, $action);

    $service = app(WhatsAppConversationService::class);
    for ($i = 0; $i <= WhatsAppConversationService::AI_REPLIES_PER_MINUTE + 3; $i++) {
        $service->receive(inboundText('hola', "wamid.{$i}"));
    }

    expect($this->gateway->sent)->toHaveCount(WhatsAppConversationService::AI_REPLIES_PER_MINUTE);
});

// --- Agent replies ---

test('an agent reply inside the 24 hour window is sent and takes the conversation from the AI', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText());
    $agent = User::factory()->create(['role' => UserRole::Sales]);
    $lead->update(['ai_paused' => false]);

    app(WhatsAppConversationService::class)->sendAgentReply($lead, $agent, 'Hola, soy Ana. Te armo la propuesta.');

    $lead->refresh();
    expect($lead->ai_paused)->toBeTrue()
        ->and($lead->agent_id)->toBe($agent->id)
        ->and($lead->status)->toBe(LeadStatus::Contacted)
        ->and($lead->unread_count)->toBe(0)
        ->and($lead->last_contacted_at)->not->toBeNull()
        ->and(collect($this->gateway->sent)->last()['text'])->toBe('Hola, soy Ana. Te armo la propuesta.')
        ->and($lead->messages()->where('sent_by_user_id', $agent->id)->first()->status)->toBe('sent');
});

test('free text is refused outside the 24 hour window but templates go through', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText());
    $lead->update(['whatsapp_last_inbound_at' => now()->subHours(25)]);
    $agent = User::factory()->create(['role' => UserRole::Sales]);

    expect(fn () => app(WhatsAppConversationService::class)->sendAgentReply($lead->fresh(), $agent, 'hola'))->toThrow(RuntimeException::class, '24 horas');

    $template = WhatsAppTemplate::create(['purpose' => 'follow_up', 'name' => 'seguimiento_viaje', 'body' => 'Hola {{1}}, ¿seguís con ganas de viajar?', 'variables_count' => 1]);
    $message = app(WhatsAppConversationService::class)->sendTemplate($lead->fresh(), $template, ['Lucía'], $agent);

    expect($message->content)->toBe('Hola Lucía, ¿seguís con ganas de viajar?')
        ->and(collect($this->gateway->sent)->last())->toMatchArray(['type' => 'template', 'template' => 'seguimiento_viaje', 'variables' => ['Lucía']]);
});

test('a failed send is recorded as failed', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText());
    $failing = new class extends FakeWhatsAppGateway
    {
        public function sendText(string $to, string $text): string
        {
            throw new RuntimeException('Meta rechazó el mensaje');
        }
    };
    app()->instance(WhatsAppGateway::class, $failing);

    $service = app(WhatsAppConversationService::class);
    expect(fn () => $service->sendAgentReply($lead, $this->admin, 'hola'))->toThrow(RuntimeException::class);
    expect($lead->messages()->where('direction', 'out')->latest('id')->first()->status)->toBe('failed');
});

// --- Inbox screen ---

test('the inbox lists conversations, replies and pauses the AI', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText(), 'Lucía');
    $lead->update(['ai_paused' => false]);
    $this->actingAs($this->admin);

    Livewire::test(WhatsAppInbox::class)
        ->assertSee('Lucía')
        ->call('open', $lead->id)
        ->assertSee('Hola, quiero viajar a Salta')
        ->set('reply', 'Hola Lucía, ¿cuántos viajan?')
        ->call('send')
        ->assertSet('reply', '');

    expect($lead->fresh()->ai_paused)->toBeTrue()->and(collect($this->gateway->sent)->last()['text'])->toBe('Hola Lucía, ¿cuántos viajan?');

    Livewire::test(WhatsAppInbox::class)->set('lead', $lead->id)->call('toggleAi');
    expect($lead->fresh()->ai_paused)->toBeFalse();
});

test('opening a conversation marks it as read', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText());
    $this->actingAs($this->admin);

    Livewire::test(WhatsAppInbox::class)->call('open', $lead->id);

    expect($lead->fresh()->unread_count)->toBe(0);
});

test('the inbox asks for a template when the window is closed', function () {
    mockAi();
    $lead = app(WhatsAppConversationService::class)->receive(inboundText());
    $lead->update(['whatsapp_last_inbound_at' => now()->subDays(2)]);
    $template = WhatsAppTemplate::create(['purpose' => 'follow_up', 'name' => 'seguimiento_viaje', 'body' => 'Hola {{1}}', 'variables_count' => 1]);
    $this->actingAs($this->admin);

    Livewire::test(WhatsAppInbox::class)
        ->call('open', $lead->id)
        ->assertSee('solo plantillas')
        ->set('templateId', $template->id)
        ->set('templateVariables', ['Lucía'])
        ->call('send');

    expect(collect($this->gateway->sent)->last())->toMatchArray(['type' => 'template', 'variables' => ['Lucía']]);
});

// --- Installment reminders by WhatsApp ---

test('installment reminders also go out by WhatsApp when a template is registered', function () {
    WhatsAppTemplate::create(['purpose' => 'installment_reminder', 'name' => 'recordatorio_cuota', 'body' => '{{1}} {{2}} {{3}} {{4}}', 'variables_count' => 4]);
    $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491155551234', 'email' => 'c@example.com']);
    $booking = Booking::factory()->create(['customer_id' => $customer->id, 'holder_name' => 'Lucía', 'status' => BookingStatus::Senado]);
    PaymentInstallment::create(['booking_id' => $booking->id, 'number' => 1, 'label' => 'Cuota 1/2', 'due_date' => today(), 'amount' => 250]);

    $this->artisan('omni:installment-reminders')->assertSuccessful();

    expect($this->gateway->sent)->toHaveCount(1)
        ->and($this->gateway->sent[0])->toMatchArray(['type' => 'template', 'to' => '+5491155551234', 'template' => 'recordatorio_cuota'])
        ->and($this->gateway->sent[0]['variables'][1])->toBe('Cuota 1/2');
});

test('the Cloud API gateway posts to the configured Graph URL with the bearer token', function () {
    Http::fake(['graph.example.test/*' => Http::response(['messages' => [['id' => 'wamid.OK']]])]);
    config(['services.whatsapp.graph_url' => 'https://graph.example.test', 'services.whatsapp.graph_version' => 'v21.0']);

    $id = (new CloudApiGateway)->sendText('+5491155551234', 'Hola');

    expect($id)->toBe('wamid.OK');
    Http::assertSent(fn ($request) => $request->url() === 'https://graph.example.test/v21.0/123/messages'
        && $request->hasHeader('Authorization', 'Bearer token')
        && $request['to'] === '5491155551234');
});
