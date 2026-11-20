<?php

use App\Ai\AgencyAi;
use App\Ai\Agents\ConciergeAgent;
use App\Ai\Agents\ReceptionistAgent;
use App\Enums\BotNoticeType;
use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\ManageBot;
use App\Filament\Admin\Resources\BotNotices\BotNoticeResource;
use App\Filament\Admin\Resources\BotNotices\Pages\ListBotNotices;
use App\Filament\Admin\Resources\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Customers\RelationManagers\LeadsRelationManager;
use App\Models\AgencySetting;
use App\Models\BotNotice;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Models\ZernioConversation;
use App\Services\AiConciergeService;
use App\Services\Bot\InquiryMergeService;
use App\Services\Bot\ReceptionistBot;
use App\Services\Bot\ReplyGuard;
use App\Services\Bot\TripData;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\ZernioGateway;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

const BOT_PHONE = '+5491155551234';

function botTurn(array $overrides = []): array
{
    return array_merge([
        'reply' => '¿Para qué fechas estás pensando?',
        'destino' => null, 'fecha_desde' => null, 'fecha_hasta' => null, 'fechas_flexibles' => null, 'presupuesto_usd' => null,
        'adultos' => null, 'menores' => null, 'edades_menores' => null, 'pasaporte' => null, 'visa' => null, 'todo_incluido' => null,
        'ciudad_salida' => null, 'es_exterior' => null, 'nombre' => null, 'resumen' => null, 'derivar' => false, 'motivo_derivacion' => null,
        'sin_mas_datos' => false, 'consulta_accion' => 'continuar', 'consulta_id' => null,
    ], $overrides);
}

function botInbound(string $text = 'Hola, quiero viajar a Punta Cana', string $id = 'wamid.B1', string $type = 'text'): array
{
    return ['id' => $id, 'from' => ltrim(BOT_PHONE, '+'), 'type' => $type, 'text' => ['body' => $text]];
}

function botCustomer(): User
{
    return User::factory()->create(['role' => UserRole::Customer, 'phone' => BOT_PHONE]);
}

function botInquiry(User $customer, array $attributes = []): Lead
{
    return Lead::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'source' => 'whatsapp',
        'channel' => Channel::WhatsApp->value,
        'status' => LeadStatus::Contacted,
        'ai_data' => [],
        'ai_paused' => false,
        'needs_human_attention' => false,
    ], $attributes));
}

function botReceive(array $message): ?Lead
{
    return app(WhatsAppConversationService::class)->receive($message);
}

beforeEach(function () {
    Mail::fake();
    Cache::flush();

    AgencySetting::factory()->create([
        'ai_provider' => 'gemini',
        'ai_api_key' => 'gemini-secret-key',
        'bot_enabled' => true,
        'bot_pause_hours' => 6,
        'bot_daily_reply_limit' => 3,
    ]);

    $this->gateway = new class implements WhatsAppGateway
    {
        /** @var list<array{to: string, text: string}> */
        public array $sent = [];

        public bool $fails = false;

        public function isConfigured(): bool
        {
            return true;
        }

        public function sendText(string $to, string $text, array $buttons = []): string
        {
            if ($this->fails) {
                throw new RuntimeException('Fuera de la ventana de 24 horas');
            }

            $this->sent[] = ['to' => $to, 'text' => $text] + ($buttons !== [] ? ['buttons' => $buttons] : []);

            return 'wamid.OUT'.count($this->sent);
        }

        public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): string
        {
            throw new LogicException('The bot never sends documents.');
        }

        public function sendList(string $to, string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): string
        {
            $this->sent[] = ['to' => $to, 'text' => $body, 'rows' => $rows];

            return 'wamid.OUT'.count($this->sent);
        }

        public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string
        {
            throw new LogicException('The bot never sends templates: it never starts a conversation.');
        }
    };

    app()->instance(WhatsAppGateway::class, $this->gateway);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

// --- Main flow ---

test('a zernio event runs the whole flow: stored raw, data learned and answered through zernio', function () {
    app()->instance(WhatsAppGateway::class, new ZernioGateway);
    config([
        'services.zernio.api_key' => 'sk_test',
        'services.zernio.whatsapp_account_id' => 'acc_luopan',
        'services.zernio.webhook_secret' => 'zernio-secret',
        'services.zernio.base_url' => 'https://zernio.example.test/api/v1',
        'services.zernio.test_phones' => null,
    ]);
    Http::fake(['zernio.example.test/*' => Http::response(['data' => ['messageId' => 'wamid.Z-OUT']])]);
    ReceptionistAgent::fake([botTurn(['destino' => 'Punta Cana', 'adultos' => 2, 'menores' => 2, 'edades_menores' => [6, 11], 'es_exterior' => true])])->preventStrayPrompts();

    $event = [
        'event' => 'message.received',
        'account' => ['accountId' => 'acc_luopan'],
        'message' => [
            'id' => 'zmsg_1', 'platformMessageId' => 'wamid.Z1', 'conversationId' => 'conv_1', 'direction' => 'incoming',
            'text' => 'Quiero ir a Punta Cana con mis dos hijos de 6 y 11', 'sender' => ['phoneNumber' => BOT_PHONE, 'name' => 'Lucía'],
        ],
    ];
    $body = json_encode($event);

    $this->call('POST', '/webhooks/zernio', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ZERNIO_SIGNATURE' => hash_hmac('sha256', $body, 'zernio-secret'),
    ], $body)->assertOk();

    $lead = Lead::sole();
    $inbound = $lead->messages()->where('role', 'user')->sole();
    $reply = $lead->messages()->where('role', 'assistant')->sole();

    expect($inbound->payload)->toBe($event)
        ->and($lead->trip_data['destino'])->toBe('Punta Cana')
        ->and($lead->trip_data['pasajeros'])->toBe(['adultos' => 2, 'menores' => 2, 'edades_menores' => [6, 11]])
        ->and($lead->trip_data['ciudad_salida'])->toBeNull()
        ->and($lead->ai_data['destino'])->toBe('Punta Cana')
        ->and($reply->sent_from)->toBe(Message::SENT_FROM_BOT)
        ->and($reply->external_id)->toBe('wamid.Z-OUT')
        ->and($reply->status)->toBe('sent')
        ->and($reply->input_tokens)->not->toBeNull();

    expect(ZernioConversation::idFor(BOT_PHONE))->toBe('conv_1');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'inbox/conversations/conv_1/messages'));
    ReceptionistAgent::assertPromptedTimes(1);
});

test('with the bot off the channels keep the previous assistant', function () {
    AgencySetting::first()->update(['bot_enabled' => false]);
    ReceptionistAgent::fake()->preventStrayPrompts();
    ConciergeAgent::fake([conciergeTurn()]);

    botReceive(botInbound());

    ReceptionistAgent::assertNeverPrompted();
    ConciergeAgent::assertPromptedTimes(1);
    expect(Lead::sole()->trip_data)->toBeNull();
});

test('closed questions go out with reply buttons and a tap is read like typed text', function () {
    ReceptionistAgent::fake([
        botTurn(['destino' => 'Salta', 'reply' => '¿Lo querés todo incluido?', 'opciones' => ['Todo incluido', 'Solo hotel', 'Una opción demasiado larga']]),
        botTurn(['todo_incluido' => true, 'reply' => '¡Genial! ¿Cuántos viajan?', 'opciones' => null]),
    ]);

    botReceive(botInbound('Quiero ir a Salta', 'wamid.1'));
    $tap = ['id' => 'wamid.2', 'from' => ltrim(BOT_PHONE, '+'), 'type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => 'opt_1_todo-incluido', 'title' => 'Todo incluido']]];
    botReceive($tap);

    expect($this->gateway->sent[0]['buttons'])->toBe(['Todo incluido', 'Solo hotel', 'Una opción demasiado'])
        ->and($this->gateway->sent[1])->not->toHaveKey('buttons')
        ->and(Lead::sole()->messages()->where('role', 'user')->latest('id')->first()->content)->toBe('Todo incluido')
        ->and(Lead::sole()->trip_data['todo_incluido'])->toBeTrue()
        ->and(Lead::sole()->messages()->where('sent_from', Message::SENT_FROM_BOT)->first()->buttons())->toBe(['Todo incluido', 'Solo hotel', 'Una opción demasiado']);
    ReceptionistAgent::assertPromptedTimes(2);
});

test('a handoff never carries buttons', function () {
    ReceptionistAgent::fake([botTurn(['derivar' => true, 'motivo_derivacion' => 'Quiere pagar', 'opciones' => ['Sí', 'No']])]);

    botReceive(botInbound('Quiero pagar'));

    expect($this->gateway->sent[0])->not->toHaveKey('buttons');
});

test('a known answer is never erased by a later turn', function () {
    ReceptionistAgent::fake([botTurn(['destino' => 'Salta', 'ciudad_salida' => 'Rosario']), botTurn(['adultos' => 2])]);

    botReceive(botInbound('Quiero ir a Salta saliendo de Rosario', 'wamid.1'));
    botReceive(botInbound('Somos dos', 'wamid.2'));

    expect(Lead::sole()->trip_data)
        ->destino->toBe('Salta')
        ->ciudad_salida->toBe('Rosario')
        ->and(Lead::sole()->trip_data['pasajeros']['adultos'])->toBe(2);
});

test('what stays true across trips is remembered on the contact', function () {
    ReceptionistAgent::fake([botTurn(['pasaporte' => true, 'edades_menores' => [6, 11], 'menores' => 2])]);

    botReceive(botInbound());

    expect(User::where('phone', BOT_PHONE)->sole()->traveler_profile)->toBe(['pasaporte' => true, 'edades_menores' => [6, 11]]);
});

test('impossible dates from the model are not stored', function () {
    ReceptionistAgent::fake([botTurn(['fecha_desde' => '2027-02-31', 'fecha_hasta' => 'enero'])]);

    botReceive(botInbound());

    expect(Lead::sole()->trip_data['fechas'])->toBe(['desde' => null, 'hasta' => null, 'flexible' => null]);
});

// --- Non-negotiable rules ---

test('asking for a person answers, raises a notice and the bot keeps attending', function () {
    ReceptionistAgent::fake([
        botTurn(['reply' => 'Belén o Nela se van a comunicar.', 'derivar' => true, 'motivo_derivacion' => 'Pide hablar con una persona']),
        botTurn(['reply' => '¿Algo más?']),
    ]);

    botReceive(botInbound('Quiero hablar con alguien', 'wamid.1'));
    botReceive(botInbound('Gracias', 'wamid.2'));

    $lead = Lead::sole();

    expect($this->gateway->sent)->toHaveCount(2)
        ->and($lead->isAiPaused())->toBeFalse()
        ->and($lead->needs_human_attention)->toBeTrue()
        ->and(BotNotice::sole())->type->toBe(BotNoticeType::Handoff)->detail->toBe('Pide hablar con una persona');
});

test('a handoff keyword hands off at once without asking the ai, also in the test chat', function () {
    AgencySetting::first()->update(['bot_handoff_keywords' => "asesor\nhablar con una persona"]);
    ReceptionistAgent::fake()->preventStrayPrompts();

    botReceive(botInbound('Hola, quiero HABLAR con una PERSONA', 'wamid.1'));
    $preview = app(ReceptionistBot::class)->preview([], TripData::empty(), 'Pasame con un asesor');

    expect($this->gateway->sent)->toHaveCount(1)
        ->and(Lead::sole()->needs_human_attention)->toBeTrue()
        ->and(BotNotice::sole()->detail)->toBe('El cliente pidió hablar con una persona (escribió «hablar con una persona»).')
        ->and($preview['handoff'])->toBe('El cliente pidió hablar con una persona (escribió «asesor»).');
    ReceptionistAgent::assertNeverPrompted();
});

test('a handoff keyword only counts as a whole word', function () {
    AgencySetting::first()->update(['bot_handoff_keywords' => 'persona']);
    ReceptionistAgent::fake([botTurn()]);

    botReceive(botInbound('Somos cuatro personas'));

    ReceptionistAgent::assertPromptedTimes(1);
    expect(BotNotice::count())->toBe(0);
});

test('a reply carrying payment details or a made-up price never leaves', function (string $reply) {
    ReceptionistAgent::fake([botTurn(['reply' => $reply])]);

    botReceive(botInbound('¿Cuánto sale y cómo pago?'));

    expect($this->gateway->sent[0]['text'])->not->toBe($reply)
        ->and($this->gateway->sent[0]['text'])->toContain('se van a comunicar')
        ->and(BotNotice::sole()->type)->toBe(BotNoticeType::Handoff);
})->with([
    'bank account' => 'Transferí al CBU 0170099220000067797370',
    'alias' => 'Mi alias es luopan.viajes',
    'card' => 'Podés pagar con tarjeta',
    'price' => 'El paquete sale USD 1.850 por persona',
    'pesos' => 'Te queda en $ 950.000',
]);

test('the guard lets through amounts the customer said and what a promotion says', function () {
    $guard = new ReplyGuard;

    expect($guard->isSafe('Perfecto, tomo nota de tus USD 3.500 de presupuesto.', customerText: 'tengo 3500 dolares'))->toBeTrue()
        ->and($guard->isSafe('Salta desde USD 499, hasta 3 cuotas.', promotions: 'Salta desde USD 499 en 3 cuotas'))->toBeTrue()
        ->and($guard->isSafe('¿Viajan 2 adultos el 10/01?'))->toBeTrue()
        ->and($guard->isSafe('Sale USD 500', customerText: 'tengo 5000 usd'))->toBeFalse()
        ->and($guard->isSafe('¿Pagás con tarjeta?', customerText: 'quiero pagar con tarjeta'))->toBeFalse();
});

test('when a person answers from the phone the bot pauses for the configured hours and then comes back', function () {
    $customer = botCustomer();
    $lead = botInquiry($customer, ['created_at' => now()->subDay()]);
    $other = botInquiry($customer, ['created_at' => now()->subDay()]);
    ReceptionistAgent::fake([botTurn(['reply' => 'Volví'])]);

    app(WhatsAppConversationService::class)->receiveBusinessAppEcho(['id' => 'wamid.E1', 'to' => BOT_PHONE, 'type' => 'text', 'text' => ['body' => 'Hola, soy Belén']]);

    expect($other->fresh()->isAiPaused())->toBeTrue();

    botReceive(botInbound('Dale gracias', 'wamid.1'));

    expect($this->gateway->sent)->toBe([]);
    ReceptionistAgent::assertNeverPrompted();

    $this->travel(6)->hours();
    $this->travel(1)->minutes();

    botReceive(botInbound('¿Seguís ahí?', 'wamid.2'));

    expect($this->gateway->sent)->toHaveCount(1)->and($lead->fresh()->isAiPaused())->toBeFalse();
});

test('answering from the inbox pauses the bot the same way, but a pause set by hand has no end', function () {
    $this->freezeSecond();
    $service = app(WhatsAppConversationService::class);
    $timed = botInquiry(botCustomer(), ['whatsapp_last_inbound_at' => now()]);
    $manual = botInquiry(User::factory()->create(['role' => UserRole::Customer, 'phone' => '+5491166667777']), ['whatsapp_last_inbound_at' => now()]);
    $service->pauseAi($manual);

    $service->sendAgentReply($timed, $this->admin, 'Hola');
    $service->sendAgentReply($manual, $this->admin, 'Hola');

    expect($timed->fresh()->ai_paused_until->equalTo(now()->addHours(6)))->toBeTrue()
        ->and($manual->fresh()->ai_paused_until)->toBeNull();

    $this->travel(7)->hours();

    expect($timed->fresh()->isAiPaused())->toBeFalse()->and($manual->fresh()->isAiPaused())->toBeTrue();
});

test('a chat the team started from the phone is not interrupted when the customer answers', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();

    app(WhatsAppConversationService::class)->receiveBusinessAppEcho(['id' => 'wamid.E1', 'to' => BOT_PHONE, 'type' => 'text', 'text' => ['body' => 'Te escribo por tu viaje']]);
    botReceive(botInbound('Hola Belén'));

    expect($this->gateway->sent)->toBe([])->and(Lead::sole()->messages()->count())->toBe(1);
    ReceptionistAgent::assertNeverPrompted();
});

test('past the daily cap the bot stops and tells the team once, also across inquiries', function () {
    ReceptionistAgent::fake(array_fill(0, 3, botTurn()));
    $customer = botCustomer();
    botInquiry($customer);
    botInquiry($customer);

    foreach (range(1, 5) as $i) {
        botReceive(botInbound("Mensaje {$i}", "wamid.{$i}"));
    }

    expect($this->gateway->sent)->toHaveCount(3)
        ->and(BotNotice::where('type', BotNoticeType::Handoff)->count())->toBe(1);
    ReceptionistAgent::assertPromptedTimes(3);

    $this->travel(1)->days();
    ReceptionistAgent::fake([botTurn()]);
    botReceive(botInbound('Otro día', 'wamid.9'));

    expect($this->gateway->sent)->toHaveCount(4);
});

test('it always answers: without a working ai the customer hears a person will write', function (array $settings, ?Throwable $failure) {
    AgencySetting::first()->update($settings);
    ReceptionistAgent::fake($failure ? [fn () => throw $failure] : []);

    botReceive(botInbound());

    expect($this->gateway->sent[0]['text'])->toContain('se van a comunicar')
        ->and(BotNotice::sole()->type)->toBe(BotNoticeType::Handoff);
})->with([
    'ai switched off' => [['ai_provider' => 'none'], null],
    'ai failing' => [[], new RuntimeException('provider down')],
]);

test('over the monthly spend limit no ai is called', function () {
    AgencySetting::first()->update(['ai_monthly_spend_limit_usd' => 1, 'ai_input_price_per_million' => 1, 'ai_output_price_per_million' => 1]);
    botInquiry(botCustomer())->messages()->create(['role' => 'assistant', 'content' => 'x', 'input_tokens' => 600_000, 'output_tokens' => 400_000]);
    ReceptionistAgent::fake()->preventStrayPrompts();

    botReceive(botInbound());

    ReceptionistAgent::assertNeverPrompted();
    expect($this->gateway->sent[0]['text'])->toContain('se van a comunicar');
});

test('last month spending does not count against the limit', function () {
    AgencySetting::first()->update(['ai_monthly_spend_limit_usd' => 1, 'ai_input_price_per_million' => 1, 'ai_output_price_per_million' => 1]);
    $message = botInquiry(botCustomer())->messages()->create(['role' => 'assistant', 'content' => 'x', 'input_tokens' => 9_000_000, 'output_tokens' => 0]);
    Message::whereKey($message->id)->update(['created_at' => now()->subMonth()->startOfMonth()]);
    ReceptionistAgent::fake([botTurn()]);

    botReceive(botInbound());

    ReceptionistAgent::assertPromptedTimes(1);
});

test('audio and images get an answer without spending ai', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();

    botReceive(['id' => 'wamid.A1', 'from' => ltrim(BOT_PHONE, '+'), 'type' => 'audio']);

    ReceptionistAgent::assertNeverPrompted();
    expect($this->gateway->sent[0]['text'])->toContain('solo puedo leer')
        ->and(BotNotice::sole()->type)->toBe(BotNoticeType::Handoff);
});

test('a reply that cannot go out leaves a notice for a person', function () {
    $this->gateway->fails = true;
    ReceptionistAgent::fake([botTurn()]);

    botReceive(botInbound());

    expect(Message::where('sent_from', Message::SENT_FROM_BOT)->sole()->status)->toBe('failed')
        ->and(BotNotice::sole()->detail)->toContain('No se pudo enviar');
});

// --- Complete data ---

test('the team is told once when the inquiry is ready to quote', function (array $answers, bool $complete) {
    ReceptionistAgent::fake([botTurn(array_merge([
        'destino' => 'Salta', 'fecha_desde' => '2027-01-10', 'fecha_hasta' => '2027-01-20', 'presupuesto_usd' => 1500,
        'adultos' => 2, 'menores' => 0, 'todo_incluido' => false, 'ciudad_salida' => 'Rosario', 'es_exterior' => false,
    ], $answers)), botTurn()]);

    botReceive(botInbound('Datos', 'wamid.1'));
    botReceive(botInbound('Otro', 'wamid.2'));

    expect(BotNotice::where('type', BotNoticeType::DataComplete)->count())->toBe($complete ? 1 : 0);
})->with([
    'domestic, no documents needed' => [[], true],
    'abroad without passport answer' => [['es_exterior' => true, 'destino' => 'Punta Cana'], false],
    'abroad with documents' => [['es_exterior' => true, 'pasaporte' => true, 'visa' => false], true],
    'minors without all their ages' => [['menores' => 2, 'edades_menores' => [6]], false],
    'minors with their ages' => [['menores' => 2, 'edades_menores' => [6, 11]], true],
    'no budget yet' => [['presupuesto_usd' => null], false],
    'no budget but the customer will not say' => [['presupuesto_usd' => null, 'sin_mas_datos' => true], true],
    'flexible with no exact dates' => [['fecha_desde' => null, 'fecha_hasta' => null, 'fechas_flexibles' => true], true],
]);

// --- Several inquiries per contact ---

test('a different trip opens an inquiry to verify and the message goes with it', function () {
    $customer = botCustomer();
    $salta = botInquiry($customer, ['trip_data' => ['destino' => 'Salta'], 'agent_id' => $this->admin->id]);
    ReceptionistAgent::fake([botTurn(['consulta_accion' => 'nueva', 'destino' => 'Cancún', 'reply' => '¡Dale! ¿Cuándo a Cancún?'])]);

    botReceive(botInbound('También quiero cotizar Cancún'));

    $new = Lead::whereKeyNot($salta->id)->sole();

    expect($new->status)->toBe(LeadStatus::PendingReview)
        ->and($new->agent_id)->toBe($this->admin->id)
        ->and($new->trip_data['destino'])->toBe('Cancún')
        ->and($new->isInsideReplyWindow())->toBeTrue()
        ->and($new->messages()->pluck('direction')->all())->toBe(['in', 'out'])
        ->and($salta->fresh()->trip_data['destino'])->toBe('Salta')
        ->and($salta->messages()->count())->toBe(0)
        ->and(BotNotice::sole())->type->toBe(BotNoticeType::VerifyNewInquiry)->lead_id->toBe($new->id);

    Mail::assertNothingSent();
});

test('an inquiry with no destination yet is not split, nor the same destination again', function (?string $current, string $said) {
    $lead = botInquiry(botCustomer(), ['trip_data' => $current ? ['destino' => $current] : null]);
    ReceptionistAgent::fake([botTurn(['consulta_accion' => 'nueva', 'destino' => $said])]);

    botReceive(botInbound());

    expect(Lead::count())->toBe(1)->and($lead->fresh()->trip_data['destino'])->toBe($said);
})->with([
    'first message' => [null, 'Cancún'],
    'same trip' => ['Cancún', 'cancún'],
]);

test('when it is not clear it only asks, and learns nothing yet', function () {
    $customer = botCustomer();
    botInquiry($customer, ['trip_data' => ['destino' => 'Salta']]);
    botInquiry($customer, ['trip_data' => ['destino' => 'Cancún']]);
    ReceptionistAgent::fake([botTurn(['consulta_accion' => 'aclarar', 'adultos' => 4, 'reply' => '¿Es por tu viaje a Salta o es una consulta nueva?'])]);

    botReceive(botInbound('Somos 4'));

    expect(Lead::count())->toBe(2)
        ->and(Lead::whereNotNull('trip_data')->get()->pluck('trip_data.pasajeros.adultos')->filter()->all())->toBe([])
        ->and($this->gateway->sent[0]['text'])->toContain('¿Es por tu viaje a Salta');
});

test('the bot can name another open inquiry, but only one of this conversation', function () {
    $customer = botCustomer();
    $salta = botInquiry($customer, ['trip_data' => ['destino' => 'Salta']]);
    $cancun = botInquiry($customer, ['trip_data' => ['destino' => 'Cancún']]);
    $foreign = botInquiry(User::factory()->create(['role' => UserRole::Customer]), ['trip_data' => ['destino' => 'Ajena']]);
    ReceptionistAgent::fake([
        botTurn(['consulta_id' => $salta->id, 'adultos' => 2]),
        botTurn(['consulta_id' => $foreign->id, 'adultos' => 9]),
    ]);

    botReceive(botInbound('Lo de Salta somos 2', 'wamid.1'));

    expect($salta->fresh()->trip_data['pasajeros']['adultos'])->toBe(2)
        ->and($salta->messages()->where('role', 'user')->count())->toBe(1)
        ->and($cancun->messages()->count())->toBe(0);

    botReceive(botInbound('Otra cosa', 'wamid.2'));

    expect($foreign->fresh()->trip_data)->toBe(['destino' => 'Ajena'])
        ->and($salta->fresh()->trip_data['pasajeros']['adultos'])->toBe(9);
});

test('an inquiry on another channel is never offered: the reply would go out there', function () {
    $customer = botCustomer();
    $whatsapp = botInquiry($customer);
    botInquiry($customer, ['channel' => Channel::Instagram->value, 'source' => 'instagram', 'channel_user_id' => 'ig_1', 'trip_data' => ['destino' => 'Bariloche']]);
    ReceptionistAgent::fake([botTurn()]);

    botReceive(botInbound());

    expect($this->gateway->sent)->toHaveCount(1)
        ->and(Message::where('sent_from', Message::SENT_FROM_BOT)->sole()->lead_id)->toBe($whatsapp->id);
    ReceptionistAgent::assertPrompted(fn ($prompt) => ! str_contains((string) $prompt->agent->instructions(), 'Bariloche'));
});

test('joining inquiries moves data and messages, keeps what the target knows and deletes the duplicate', function () {
    $customer = botCustomer();
    $target = botInquiry($customer, ['trip_data' => ['destino' => 'Salta', 'ciudad_salida' => null]]);
    $duplicate = botInquiry($customer, ['status' => LeadStatus::PendingReview, 'trip_data' => ['destino' => 'Cafayate', 'ciudad_salida' => 'Rosario', 'todo_incluido' => true]]);
    $message = $duplicate->messages()->create(['role' => 'user', 'content' => 'Hola']);
    BotNotice::raise($duplicate, BotNoticeType::VerifyNewInquiry);
    $complete = BotNotice::raise($duplicate, BotNoticeType::DataComplete);

    app(InquiryMergeService::class)->merge($duplicate, $target);

    expect(Lead::find($duplicate->id))->toBeNull()
        ->and($message->fresh()->lead_id)->toBe($target->id)
        ->and($target->fresh()->trip_data)->destino->toBe('Salta')->ciudad_salida->toBe('Rosario')->todo_incluido->toBeTrue()
        ->and($complete->fresh()->lead_id)->toBe($target->id)
        ->and(BotNotice::where('type', BotNoticeType::VerifyNewInquiry)->exists())->toBeFalse();
});

test('inquiries of different customers cannot be joined', function () {
    $mine = botInquiry(botCustomer());
    $theirs = botInquiry(User::factory()->create(['role' => UserRole::Customer]));

    expect(fn () => app(InquiryMergeService::class)->merge($mine, $theirs))->toThrow(RuntimeException::class);
    expect(Lead::find($mine->id))->not->toBeNull();
});

// --- Notices tray ---

test('the tray confirms a new inquiry', function () {
    $this->actingAs($this->admin);
    $pending = botInquiry(botCustomer(), ['status' => LeadStatus::PendingReview]);
    $notice = BotNotice::raise($pending, BotNoticeType::VerifyNewInquiry);

    Livewire::test(ListBotNotices::class)->callAction(TestAction::make('confirmNew')->table($notice))->assertHasNoActionErrors();

    expect($pending->fresh()->status)->toBe(LeadStatus::New)->and($notice->fresh()->read_at)->not->toBeNull();
});

test('the tray joins into an open inquiry of the same customer only', function () {
    $this->actingAs($this->admin);
    $customer = botCustomer();
    $target = botInquiry($customer);
    $pending = botInquiry($customer, ['status' => LeadStatus::PendingReview]);
    $foreign = botInquiry(User::factory()->create(['role' => UserRole::Customer]));
    $notice = BotNotice::raise($pending, BotNoticeType::VerifyNewInquiry);

    Livewire::test(ListBotNotices::class)
        ->callAction(TestAction::make('mergeInto')->table($notice), ['target_id' => $foreign->id])
        ->assertHasActionErrors(['target_id']);

    expect(Lead::find($pending->id))->not->toBeNull();

    Livewire::test(ListBotNotices::class)->callAction(TestAction::make('mergeInto')->table($notice), ['target_id' => $target->id])->assertHasNoActionErrors();

    expect(Lead::find($pending->id))->toBeNull();
});

test('notices and their badge only show during working hours', function () {
    $this->actingAs($this->admin);
    AgencySetting::first()->update(['bot_business_days' => [1, 2, 3, 4, 5], 'bot_hours_from' => '09:00', 'bot_hours_to' => '18:00']);
    $notice = BotNotice::factory()->create(['read_at' => null]);

    $this->travelTo(now()->next('Monday')->setTime(10, 0));
    expect(BotNoticeResource::getNavigationBadge())->toBe('1');
    Livewire::test(ListBotNotices::class)->assertCanSeeTableRecords([$notice]);

    $this->travelTo(now()->setTime(18, 0));
    expect(BotNoticeResource::getNavigationBadge())->toBeNull();
    Livewire::test(ListBotNotices::class)->assertCanNotSeeTableRecords([$notice]);

    $this->travelTo(now()->next('Saturday')->setTime(10, 0));
    expect(BotNoticeResource::getNavigationBadge())->toBeNull();
});

test('people without access to leads cannot open the tray or the panel', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Customer]));

    expect(BotNoticeResource::canAccess())->toBeFalse()->and(ManageBot::canAccess())->toBeFalse();
});

// --- Panel ---

test('only an admin configures the bot', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Sales]));

    expect(ManageBot::canAccess())->toBeFalse();
});

test('the saved key is stored encrypted, never reaches the browser and is kept when left empty', function () {
    $this->actingAs($this->admin);

    $page = Livewire::test(ManageBot::class)->assertDontSee('gemini-secret-key');

    expect($page->get('data.ai_api_key'))->toBeNull();

    $page->fillForm(['bot_instructions' => 'Tono cercano'])->call('save')->assertHasNoFormErrors();

    expect(AgencySetting::first())->ai_api_key->toBe('gemini-secret-key')->bot_instructions->toBe('Tono cercano');

    $page->fillForm(['ai_api_key' => 'new-secret-9876'])->call('save')->assertHasNoFormErrors();

    expect(AgencySetting::first()->maskedAiApiKey())->toBe('••••••••9876')
        ->and(DB::table('agency_settings')->value('ai_api_key'))->not->toContain('new-secret');
});

test('a spend limit needs the prices to be computed and working hours must make sense', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageBot::class)
        ->fillForm(['ai_monthly_spend_limit_usd' => 10, 'bot_hours_from' => '18:00', 'bot_hours_to' => '09:00'])
        ->call('save')
        ->assertHasFormErrors(['ai_input_price_per_million', 'ai_output_price_per_million', 'bot_hours_to']);
});

test('the openai compatible provider needs its own url and uses it', function () {
    AgencySetting::first()->update(['ai_provider' => 'openai_compatible', 'ai_model' => 'mi-modelo', 'ai_base_url' => null]);
    Cache::flush();

    expect(fn () => (new AgencyAi)->resolve())->toThrow(Exception::class, 'URL');

    AgencySetting::first()->update(['ai_base_url' => 'https://llm.example.test/v1']);

    Http::fake(['llm.example.test/*' => Http::response([
        'id' => 'c1', 'model' => 'mi-modelo',
        'choices' => [['index' => 0, 'finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'OK']]],
        'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
    ])]);

    expect(app(AiConciergeService::class)->testConnection())->toBeNull();

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://llm.example.test/v1/') && $request['model'] === 'mi-modelo');
});

test('promotions are offered to the bot only while in force', function () {
    AgencySetting::first()->update(['bot_promotions' => [
        ['title' => 'Vigente', 'text' => 'Hoy', 'starts_on' => now()->subDay()->toDateString(), 'ends_on' => now()->addDay()->toDateString()],
        ['title' => 'Vencida', 'text' => 'Ayer', 'ends_on' => now()->subDay()->toDateString()],
        ['title' => 'Futura', 'text' => 'Pronto', 'starts_on' => now()->addDay()->toDateString()],
    ]]);

    expect(array_column(get_agency_settings()->currentPromotions(), 'title'))->toBe(['Vigente']);
});

test('the test chat runs the bot but stores, sends and notices nothing', function () {
    $this->actingAs($this->admin);
    ReceptionistAgent::fake([
        botTurn(['reply' => '¿Cuándo querés ir?', 'destino' => 'Punta Cana']),
        botTurn(['reply' => 'Sale USD 999', 'derivar' => false]),
    ]);

    $page = Livewire::test(ManageBot::class)->call('startTest')->set('testMessage', 'Quiero ir a Punta Cana')->call('sendTest');

    expect($page->get('testTrip.destino'))->toBe('Punta Cana')
        ->and($page->get('testChat.1.content'))->toBe('¿Cuándo querés ir?');

    $page->set('testMessage', '¿Cuánto sale?')->call('sendTest');

    expect($page->get('testChat.3.content'))->toContain('se van a comunicar')
        ->and(Message::count())->toBe(0)
        ->and(Lead::count())->toBe(0)
        ->and(BotNotice::count())->toBe(0)
        ->and($this->gateway->sent)->toBe([]);
});

// --- Contact view ---

test('the contact shows their inquiries and what is remembered about them', function () {
    $this->actingAs($this->admin);
    $customer = botCustomer();
    $customer->forceFill(['traveler_profile' => ['pasaporte' => true, 'edades_menores' => [6, 11]]])->save();
    $inquiry = botInquiry($customer, ['trip_data' => ['destino' => 'Punta Cana']]);
    $foreign = botInquiry(User::factory()->create(['role' => UserRole::Customer]));

    Livewire::test(LeadsRelationManager::class, ['ownerRecord' => $customer, 'pageClass' => EditCustomer::class])
        ->assertCanSeeTableRecords([$inquiry])
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertSee('Punta Cana');

    Livewire::test(EditCustomer::class, ['record' => $customer->id])->assertSee('Pasaporte: Sí')->assertSee('6, 11');
});

test('an older lead starts from what the previous assistant learned', function () {
    $lead = botInquiry(botCustomer(), ['ai_data' => ['destino' => 'Mendoza', 'ciudad_salida' => 'Córdoba']]);

    expect(TripData::of($lead))->destino->toBe('Mendoza')->ciudad_salida->toBe('Córdoba');
});
