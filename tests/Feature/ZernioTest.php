<?php

use App\Jobs\ProcessIncomingWhatsApp;
use App\Jobs\ProcessWhatsAppBusinessAppEcho;
use App\Models\Lead;
use App\Models\WhatsAppTemplate;
use App\Models\ZernioConversation;
use App\Services\WhatsApp\CloudApiGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\ZernioGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'services.zernio.api_key' => 'sk_test',
        'services.zernio.whatsapp_account_id' => 'acc_luopan',
        'services.zernio.webhook_secret' => 'zernio-secret',
        'services.zernio.base_url' => 'https://zernio.example.test/api/v1',
    ]);
});

function zernioPost(array $payload, ?string $secret = 'zernio-secret'): TestResponse
{
    $body = json_encode($payload);
    $headers = ['CONTENT_TYPE' => 'application/json'];

    if ($secret !== null) {
        $headers['HTTP_X_ZERNIO_SIGNATURE'] = hash_hmac('sha256', $body, $secret);
    }

    return test()->call('POST', '/webhooks/zernio', [], [], [], $headers, $body);
}

function zernioEvent(string $event, array $message, string $account = 'acc_luopan', array $extra = []): array
{
    return ['id' => 'evt_'.$event, 'event' => $event, 'account' => ['accountId' => $account], 'message' => $message] + $extra;
}

function zernioIncoming(string $text = 'Hola, quiero viajar a Salta'): array
{
    return zernioEvent('message.received', [
        'id' => 'zmsg_1', 'platformMessageId' => 'wamid.Z1', 'conversationId' => 'conv_1', 'direction' => 'incoming',
        'text' => $text, 'sender' => ['phoneNumber' => '+5491155551234', 'name' => 'Lucía'],
    ]);
}

test('events without a valid signature are rejected', function () {
    Queue::fake();

    zernioPost(zernioIncoming(), null)->assertStatus(401);
    zernioPost(zernioIncoming(), 'wrong')->assertStatus(400);

    Queue::assertNothingPushed();
});

test('a received message is queued in the cloud api shape and its conversation remembered', function () {
    Queue::fake();

    zernioPost(zernioIncoming())->assertOk();

    Queue::assertPushed(ProcessIncomingWhatsApp::class, fn ($job) => $job->message === [
        'id' => 'wamid.Z1', 'from' => '5491155551234', 'type' => 'text', 'text' => ['body' => 'Hola, quiero viajar a Salta'],
    ] && $job->profileName === 'Lucía');

    expect(ZernioConversation::idFor('+5491155551234'))->toBe('conv_1');
});

test('events of another zernio account are ignored', function () {
    Queue::fake();

    zernioPost(zernioEvent('message.received', zernioIncoming()['message'], 'acc_other'))->assertOk();

    Queue::assertNothingPushed();
});

test('only messages written on the phone come back as business app echoes', function () {
    Queue::fake();
    $sent = fn (string $source) => zernioEvent('message.sent', [
        'id' => 'zmsg_2', 'platformMessageId' => 'wamid.ECHO', 'conversationId' => 'conv_1', 'direction' => 'outgoing', 'text' => 'Te paso la info', 'source' => $source,
    ], extra: ['conversation' => ['participantId' => '5491155551234']]);

    zernioPost($sent('cloud_api'))->assertOk();
    Queue::assertNothingPushed();

    zernioPost($sent('whatsapp_business_app'))->assertOk();
    Queue::assertPushed(ProcessWhatsAppBusinessAppEcho::class, fn ($job) => $job->echo['id'] === 'wamid.ECHO' && $job->echo['to'] === '5491155551234');
});

test('delivery and read events update our message', function () {
    $message = Lead::factory()->create()->messages()->create(['role' => 'assistant', 'content' => 'Hola', 'external_id' => 'wamid.OUT', 'status' => 'sent']);

    zernioPost(zernioEvent('message.read', ['platformMessageId' => 'wamid.OUT']))->assertOk();

    expect($message->fresh()->status)->toBe('read');
});

test('replies go to the remembered conversation with the api key', function () {
    Http::fake(['zernio.example.test/*' => Http::response(['success' => true, 'data' => ['messageId' => 'wamid.SENT']])]);
    ZernioConversation::remember('+5491155551234', 'conv_1');

    expect((new ZernioGateway)->sendText('+5491155551234', 'Hola'))->toBe('wamid.SENT');

    Http::assertSent(fn ($request) => $request->url() === 'https://zernio.example.test/api/v1/inbox/conversations/conv_1/messages'
        && $request->hasHeader('Authorization', 'Bearer sk_test')
        && $request['accountId'] === 'acc_luopan'
        && $request['message'] === 'Hola');
});

test('free text to a number without a conversation asks for a template', function () {
    Http::fake();

    expect(fn () => (new ZernioGateway)->sendText('+5491166660000', 'Hola'))->toThrow(RuntimeException::class, 'plantilla');
    Http::assertNothingSent();
});

test('a template opens the conversation and remembers it', function () {
    Http::fake(['zernio.example.test/*' => Http::response(['success' => true, 'data' => ['conversationId' => 'conv_9', 'messageId' => 'wamid.T']])]);
    $template = WhatsAppTemplate::create(['purpose' => 'quote_ready', 'name' => 'luopan_presupuesto_listo', 'language' => 'es_AR', 'body' => 'Hola {{1}}, tu propuesta para {{2}}.', 'variables_count' => 2]);

    expect((new ZernioGateway)->sendTemplate('+5491166660000', $template, ['Ana', 'Salta']))->toBe('wamid.T');

    Http::assertSent(fn ($request) => $request->url() === 'https://zernio.example.test/api/v1/inbox/conversations'
        && $request['participantId'] === '5491166660000'
        && $request['templateName'] === 'luopan_presupuesto_listo'
        && $request['templateParams'] === ['Ana', 'Salta']);
    expect(ZernioConversation::idFor('+5491166660000'))->toBe('conv_9');
});

test('the whatsapp provider is chosen in the config', function () {
    app()->forgetInstance(WhatsAppGateway::class);
    config(['services.whatsapp.provider' => 'zernio']);
    expect(app(WhatsAppGateway::class))->toBeInstanceOf(ZernioGateway::class);

    app()->forgetInstance(WhatsAppGateway::class);
    config(['services.whatsapp.provider' => 'cloud']);
    expect(app(WhatsAppGateway::class))->toBeInstanceOf(CloudApiGateway::class);
});
