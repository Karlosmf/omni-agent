<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AiProvider;
use App\Filament\Admin\Schemas\AiProviderFields;
use App\Models\AgencySetting;
use App\Services\AiConciergeService;
use App\Services\Bot\ReceptionistBot;
use App\Services\Bot\TripData;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Throwable;

/**
 * Everything the receptionist bot reads, and an internal chat to try it before it talks to customers.
 */
class ManageBot extends Page implements HasForms
{
    use InteractsWithForms;

    private const FIELDS = [
        'bot_enabled', 'bot_instructions', 'bot_handoff_rules', 'bot_handoff_keywords', 'bot_promotions', 'bot_business_days', 'bot_hours_from', 'bot_hours_to',
        'bot_pause_hours', 'bot_daily_reply_limit', 'ai_provider', 'ai_base_url', 'ai_model', 'ai_monthly_spend_limit_usd',
        'ai_input_price_per_million', 'ai_output_price_per_million',
    ];

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static \UnitEnum|string|null $navigationGroup = 'Chat';

    protected static ?string $navigationLabel = 'Bot recepcionista';

    protected static ?string $title = 'Bot recepcionista';

    protected static ?string $slug = 'bot';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.admin.pages.manage-bot';

    public ?array $data = [];

    public bool $isTesting = false;

    public string $testMessage = '';

    /** @var list<array{role: string, content: string, handoff?: ?string, buttons?: list<string>}> */
    public array $testChat = [];

    /** @var array<string, mixed> */
    public array $testTrip = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(AgencySetting::first()?->only(self::FIELDS) ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->action('save'),
            Action::make('test')
                ->label('Probar bot')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('gray')
                ->action('startTest'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Estado')
                    ->schema([
                        Toggle::make('bot_enabled')
                            ->label('El bot atiende los mensajes de WhatsApp, Instagram y Messenger')
                            ->helperText('Apagado, los canales siguen con el asistente anterior. Probalo antes de encenderlo.'),
                    ]),
                Section::make('Qué dice')
                    ->schema([
                        Textarea::make('bot_instructions')
                            ->label('Instrucciones')
                            ->helperText('En lenguaje natural: qué preguntar, tono, preguntas frecuentes. No hace falta repetir las reglas fijas: nunca da pagos ni precios finales y solo habla de la agencia.')
                            ->rows(8),
                        Textarea::make('bot_handoff_rules')
                            ->label('Cuándo derivar a una persona')
                            ->helperText('Además de las reglas fijas: si no sabe, si piden una persona, o si quieren reservar o pagar.')
                            ->rows(4),
                        Textarea::make('bot_handoff_keywords')
                            ->label('Palabras que derivan al instante')
                            ->helperText('Una por línea o separadas por comas (ej: asesor, hablar con una persona, reclamo). Si el cliente escribe una, el bot avisa al equipo y responde que una persona se va a comunicar, sin consultar a la IA. No importan los acentos ni las mayúsculas, y solo cuenta la palabra entera.')
                            ->rows(2),
                    ]),
                Section::make('Promociones vigentes')
                    ->description('El bot las explica tal cual, sin inventar. Solo menciona las que están dentro de su vigencia.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('bot_promotions')
                            ->hiddenLabel()
                            ->addActionLabel('Agregar promoción')
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->collapsible()
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')->label('Título')->required()->columnSpanFull(),
                                Textarea::make('text')->label('Texto')->required()->rows(3)->columnSpanFull(),
                                TextInput::make('channel')->label('Canal donde se publicó')->placeholder('Ej: Instagram')->columnSpanFull(),
                                DatePicker::make('starts_on')->label('Vigente desde')->native(false),
                                DatePicker::make('ends_on')->label('Vigente hasta')->native(false)->afterOrEqual('starts_on'),
                            ]),
                    ]),
                Section::make('Horario y topes')
                    ->schema([
                        CheckboxList::make('bot_business_days')
                            ->label('Días laborales del equipo')
                            ->helperText('El bot responde siempre. El horario solo decide cuándo se muestran los avisos.')
                            ->options([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'])
                            ->columns(4),
                        Grid::make(2)->schema([
                            TimePicker::make('bot_hours_from')->label('Desde')->seconds(false)->required(fn (Get $get): bool => filled($get('bot_hours_to'))),
                            TimePicker::make('bot_hours_to')->label('Hasta')->seconds(false)->after('bot_hours_from')->required(fn (Get $get): bool => filled($get('bot_hours_from'))),
                            TextInput::make('bot_pause_hours')
                                ->label('Horas de pausa tras una respuesta humana')
                                ->numeric()->integer()->minValue(1)->maxValue(720)->default(12)->required(),
                            TextInput::make('bot_daily_reply_limit')
                                ->label('Máximo de respuestas del bot por conversación por día')
                                ->numeric()->integer()->minValue(1)->maxValue(500)->default(30)->required(),
                        ]),
                    ]),
                Section::make('Proveedor de IA')
                    ->description('Es la misma IA que usa toda la agencia: cambiarla acá la cambia también en Marca Blanca.')
                    ->schema([
                        Grid::make(2)->schema([
                            ...AiProviderFields::make(),
                            TextInput::make('ai_monthly_spend_limit_usd')
                                ->label('Límite de gasto mensual (USD)')
                                ->numeric()->minValue(0.01)
                                ->placeholder('Sin límite')
                                ->helperText(fn (): string => 'Gastado este mes (estimado): USD '.number_format(app(AiConciergeService::class)->monthlySpendUsd(), 2).'. Al llegar al límite el bot avisa al equipo y responde que una persona se va a comunicar.')
                                ->columnSpanFull(),
                            TextInput::make('ai_input_price_per_million')
                                ->label('Precio por millón de tokens de entrada (USD)')
                                ->numeric()->minValue(0)
                                ->required(fn (Get $get): bool => filled($get('ai_monthly_spend_limit_usd')))
                                ->helperText('Sale de la lista de precios del proveedor. Sin precios no se puede calcular el gasto.'),
                            TextInput::make('ai_output_price_per_million')
                                ->label('Precio por millón de tokens de salida (USD)')
                                ->numeric()->minValue(0)
                                ->required(fn (Get $get): bool => filled($get('ai_monthly_spend_limit_usd'))),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (blank($data['ai_api_key'] ?? null)) {
            unset($data['ai_api_key']);
        }

        $provider = $data['ai_provider'] ?? null;

        if (($provider instanceof AiProvider ? $provider : AiProvider::tryFrom((string) $provider)) !== AiProvider::OpenAiCompatible) {
            $data['ai_base_url'] = null;
        }

        $settings = AgencySetting::first() ?: new AgencySetting;
        $settings->fill($data)->save();

        $this->data['ai_api_key'] = null;

        Notification::make()->title('Configuración del bot guardada')->success()->send();
    }

    public function startTest(): void
    {
        $this->save();

        $this->isTesting = true;
        $this->resetTest();
    }

    public function resetTest(): void
    {
        $this->testChat = [];
        $this->testMessage = '';
        $this->testTrip = TripData::empty();
    }

    public function sendTest(ReceptionistBot $bot): void
    {
        $text = trim($this->testMessage);

        if ($text === '') {
            return;
        }

        $history = array_map(fn (array $message) => ['role' => $message['role'], 'content' => $message['content']], $this->testChat);

        try {
            $result = $bot->preview($history, $this->testTrip, $text);
        } catch (Throwable $e) {
            Notification::make()->title('La IA no respondió')->body(str($e->getMessage())->limit(250))->danger()->send();

            return;
        }

        $this->testChat[] = ['role' => 'user', 'content' => $text];
        $this->testChat[] = ['role' => 'assistant', 'content' => $result['reply'], 'handoff' => $result['handoff'], 'buttons' => $result['buttons']];
        $this->testTrip = $result['trip'];
        $this->testMessage = '';
    }

    /**
     * @return array<string, string>
     */
    public function testSummary(): array
    {
        return TripData::summary($this->testTrip);
    }
}
