<?php

use App\Ai\Agents\ReceptionistAgent;
use App\Enums\BotNoticeType;
use App\Enums\Channel;
use App\Enums\ChatMenuAction;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ChatMenus\ChatMenuResource;
use App\Filament\Admin\Resources\ChatMenus\Pages\ManageChatMenus;
use App\Models\AgencySetting;
use App\Models\BotNotice;
use App\Models\ChatMenu;
use App\Models\Lead;
use App\Models\Message;
use App\Models\TravelPackage;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

const MENU_PHONE = '+5491155557777';

function menuText(string $text, string $id): array
{
    return ['id' => $id, 'from' => ltrim(MENU_PHONE, '+'), 'type' => 'text', 'text' => ['body' => $text]];
}

/**
 * A tapped list row, as the Cloud API sends it and as the Zernio handler rewrites a Zernio tap.
 */
function menuTap(string $rowId, string $title, string $id): array
{
    return ['id' => $id, 'from' => ltrim(MENU_PHONE, '+'), 'type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => $rowId, 'title' => $title]]];
}

function menuReceive(array $message): ?Lead
{
    return app(WhatsAppConversationService::class)->receive($message);
}

function menuTurn(string $reply = '¿Para qué fechas?'): array
{
    return [
        'reply' => $reply, 'destino' => null, 'fecha_desde' => null, 'fecha_hasta' => null, 'fechas_flexibles' => null, 'presupuesto_usd' => null,
        'adultos' => null, 'menores' => null, 'edades_menores' => null, 'pasaporte' => null, 'visa' => null, 'todo_incluido' => null,
        'ciudad_salida' => null, 'es_exterior' => null, 'nombre' => null, 'resumen' => null, 'derivar' => false, 'motivo_derivacion' => null,
        'sin_mas_datos' => false, 'consulta_accion' => 'continuar', 'consulta_id' => null,
    ];
}

beforeEach(function () {
    Mail::fake();
    Cache::flush();

    AgencySetting::factory()->create([
        'ai_provider' => 'gemini',
        'ai_api_key' => 'gemini-secret-key',
        'bot_enabled' => true,
        'bot_pause_hours' => 6,
        'bot_daily_reply_limit' => 30,
    ]);

    $this->gateway = new class implements WhatsAppGateway
    {
        /** @var list<array<string, mixed>> */
        public array $sent = [];

        public bool $fails = false;

        public function isConfigured(): bool
        {
            return true;
        }

        public function sendText(string $to, string $text, array $buttons = []): string
        {
            $this->sent[] = ['type' => 'text', 'to' => $to, 'text' => $text];

            return 'wamid.OUT'.count($this->sent);
        }

        public function sendList(string $to, string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): string
        {
            if ($this->fails) {
                throw new RuntimeException('Fuera de la ventana de 24 horas');
            }

            $this->sent[] = ['type' => 'list', 'to' => $to, 'text' => $body, 'button' => $button, 'rows' => $rows, 'section' => $sectionTitle];

            return 'wamid.OUT'.count($this->sent);
        }

        public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): string
        {
            throw new LogicException('Menus never send documents.');
        }

        public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string
        {
            throw new LogicException('Menus never send templates.');
        }
    };

    app()->instance(WhatsAppGateway::class, $this->gateway);
});

test('a keyword opens the destinations menu without asking the ai', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();
    TravelPackage::factory()->count(2)->create(['destination' => 'Punta Cana', 'is_active' => true]);
    TravelPackage::factory()->create(['destination' => 'Bariloche', 'is_active' => true]);
    TravelPackage::factory()->create(['destination' => 'Kenia', 'is_active' => false]);
    ChatMenu::factory()->create(['name' => 'Destinos', 'keywords' => "destinos\nver paquetes", 'button_text' => 'Ver destinos']);

    menuReceive(menuText('Hola! Quiero ver paquetes', 'wamid.1'));

    $sent = $this->gateway->sent[0];

    expect($sent)->toMatchArray(['type' => 'list', 'to' => MENU_PHONE, 'button' => 'Ver destinos', 'section' => 'Destinos'])
        ->and(array_column($sent['rows'], 'title'))->toBe(['Bariloche', 'Punta Cana'])
        ->and(array_column($sent['rows'], 'description'))->toBe(['1 paquete', '2 paquetes'])
        ->and(Lead::sole()->messages()->where('role', 'assistant')->sole())
        ->sent_from->toBe(Message::SENT_FROM_MENU)
        ->status->toBe('sent')
        ->external_id->toBe('wamid.OUT1');
    expect(Lead::sole()->messages()->where('role', 'assistant')->sole()->buttons())->toBe(['Bariloche', 'Punta Cana']);
    ReceptionistAgent::assertNeverPrompted();
});

test('a chosen destination opens only its packages and a chosen package goes to the bot as the customer words', function () {
    ReceptionistAgent::fake([menuTurn('¡Buenísimo! ¿Para qué fechas?')]);
    $cancun = TravelPackage::factory()->create(['title' => 'Cancún Todo Incluido', 'destination' => 'Cancún', 'nights' => 7, 'summary' => 'Hotel frente al mar', 'is_active' => true]);
    TravelPackage::factory()->create(['title' => 'Roma Clásica', 'destination' => 'Roma', 'is_active' => true]);
    $packages = ChatMenu::factory()->packages()->create();
    ChatMenu::factory()->opens($packages)->create(['keywords' => 'destinos']);

    menuReceive(menuText('destinos', 'wamid.1'));
    $cancunRow = collect($this->gateway->sent[0]['rows'])->firstWhere('title', 'Cancún');
    menuReceive(menuTap($cancunRow['id'], 'Cancún', 'wamid.2'));

    expect($this->gateway->sent[1]['rows'])->toBe([['id' => 'menu:'.$packages->id.':p:'.$cancun->id, 'title' => 'Cancún Todo Incluido', 'description' => '7 noches · Hotel frente al mar']]);

    menuReceive(menuTap($this->gateway->sent[1]['rows'][0]['id'], '', 'wamid.3'));

    $lead = Lead::sole();

    expect($lead->travel_package_id)->toBe($cancun->id)
        ->and($lead->messages()->where('external_id', 'wamid.3')->sole()->content)->toBe('Cancún Todo Incluido')
        ->and($this->gateway->sent[2])->toMatchArray(['type' => 'text', 'text' => '¡Buenísimo! ¿Para qué fechas?']);
    ReceptionistAgent::assertPrompted(fn ($prompt) => $prompt->prompt === 'Cancún Todo Incluido');
});

test('long menus show nine options and a see more row that brings the rest', function () {
    $menu = ChatMenu::factory()->withOptions(collect(range(1, 12))->map(fn (int $n) => ['title' => "Opción {$n}"])->all())->create(['keywords' => 'ayuda']);

    menuReceive(menuText('ayuda', 'wamid.1'));
    $first = $this->gateway->sent[0]['rows'];

    expect($first)->toHaveCount(10)
        ->and($first[8]['title'])->toBe('Opción 9')
        ->and($first[9])->toBe(['id' => 'menu:'.$menu->id.':m:9:', 'title' => 'Ver más…', 'description' => 'Siguientes opciones']);

    menuReceive(menuTap($first[9]['id'], 'Ver más…', 'wamid.2'));

    expect(array_column($this->gateway->sent[1]['rows'], 'title'))->toBe(['Opción 10', 'Opción 11', 'Opción 12']);
});

test('an option can answer a fixed text with the chosen option', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();
    ChatMenu::factory()->withOptions([['title' => 'Horarios'], ['title' => 'Dirección']])->create([
        'keywords' => 'info', 'on_select' => ChatMenuAction::Reply, 'reply_text' => 'Elegiste {opcion}: te pasamos los datos.',
    ]);

    menuReceive(menuText('info', 'wamid.1'));
    menuReceive(menuTap($this->gateway->sent[0]['rows'][1]['id'], 'Dirección', 'wamid.2'));

    expect($this->gateway->sent[1])->toMatchArray(['type' => 'text', 'text' => 'Elegiste Dirección: te pasamos los datos.']);
    ReceptionistAgent::assertNeverPrompted();
});

test('an option taken down since the menu was sent is explained and the menu comes again', function () {
    $package = TravelPackage::factory()->create(['title' => 'Salta y Jujuy', 'destination' => 'Salta', 'is_active' => true]);
    ChatMenu::factory()->packages()->create(['keywords' => 'paquetes']);

    menuReceive(menuText('paquetes', 'wamid.1'));
    $package->update(['is_active' => false]);
    TravelPackage::factory()->create(['title' => 'Mendoza Vinos', 'destination' => 'Mendoza', 'is_active' => true]);
    menuReceive(menuTap($this->gateway->sent[0]['rows'][0]['id'], 'Salta y Jujuy', 'wamid.2'));

    expect($this->gateway->sent[1])->toMatchArray(['type' => 'text', 'text' => 'Esa opción ya no está disponible. Te muestro las opciones actualizadas.'])
        ->and(array_column($this->gateway->sent[2]['rows'], 'title'))->toBe(['Mendoza Vinos'])
        ->and(Lead::sole()->travel_package_id)->toBeNull();
});

test('no menu opens while a person attends the chat', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();
    ChatMenu::factory()->create(['keywords' => 'destinos']);
    $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => MENU_PHONE]);
    Lead::factory()->create([
        'customer_id' => $customer->id, 'source' => 'whatsapp', 'channel' => Channel::WhatsApp->value, 'status' => LeadStatus::Contacted,
        'ai_paused' => true, 'ai_paused_until' => now()->addHours(3),
    ]);

    menuReceive(menuText('destinos', 'wamid.1'));

    expect($this->gateway->sent)->toBe([]);
});

test('a menu that cannot go out leaves a notice for a person', function () {
    $this->gateway->fails = true;
    TravelPackage::factory()->create(['destination' => 'Salta', 'is_active' => true]);
    ChatMenu::factory()->create(['keywords' => 'destinos']);

    menuReceive(menuText('destinos', 'wamid.1'));

    expect(Lead::sole()->messages()->where('role', 'assistant')->sole()->status)->toBe('failed')
        ->and(Lead::sole()->needs_human_attention)->toBeTrue()
        ->and(BotNotice::sole()->type)->toBe(BotNoticeType::Handoff);
});

test('an admin creates a menu that answers a fixed text, which then needs that text', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(ManageChatMenus::class)
        ->callAction('create', ['name' => 'Info', 'keywords' => 'info', 'source' => 'options', 'options' => [['title' => 'Horarios']], 'body' => '¿Qué necesitás?', 'on_select' => 'reply'])
        ->assertHasFormErrors(['reply_text' => 'required']);

    Livewire::test(ManageChatMenus::class)
        ->callAction('create', ['name' => 'Info', 'keywords' => 'info', 'source' => 'options', 'options' => [['title' => 'Horarios']], 'body' => '¿Qué necesitás?', 'on_select' => 'reply', 'reply_text' => 'Atendemos de 9 a 18.', 'is_active' => true])
        ->assertHasNoFormErrors();

    expect(ChatMenu::sole())
        ->on_select->toBe(ChatMenuAction::Reply)
        ->typedOptions()->toBe([['title' => 'Horarios', 'description' => null]]);
});

test('only admins manage the chat menus', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Customer]));

    expect(ChatMenuResource::canAccess())->toBeFalse();
});

test('a tap on an option that was removed after the menu went out never lands on another option', function () {
    ReceptionistAgent::fake()->preventStrayPrompts();
    $menu = ChatMenu::factory()->withOptions([['title' => 'Bariloche'], ['title' => 'Mendoza']])->create([
        'keywords' => 'info', 'on_select' => ChatMenuAction::Reply, 'reply_text' => 'Elegiste {opcion}.',
    ]);

    menuReceive(menuText('info', 'wamid.1'));
    $menu->update(['options' => [['title' => 'Mendoza']]]);
    menuReceive(menuTap($this->gateway->sent[0]['rows'][0]['id'], 'Bariloche', 'wamid.2'));

    expect($this->gateway->sent[1])->toMatchArray(['type' => 'text', 'text' => 'Esa opción ya no está disponible. Te muestro las opciones actualizadas.'])
        ->and(array_column($this->gateway->sent[2]['rows'], 'title'))->toBe(['Mendoza']);
    ReceptionistAgent::assertNeverPrompted();
});

test('a destination written in different ways is one row with all its packages', function () {
    TravelPackage::factory()->count(2)->create(['destination' => 'Punta Cana', 'is_active' => true]);
    TravelPackage::factory()->create(['destination' => 'punta cana', 'is_active' => true]);
    ChatMenu::factory()->create(['keywords' => 'destinos']);

    menuReceive(menuText('destinos', 'wamid.1'));

    expect($this->gateway->sent[0]['rows'])->toHaveCount(1)
        ->and($this->gateway->sent[0]['rows'][0]['description'])->toBe('3 paquetes');
});
