<?php

namespace App\Filament\Admin\Resources\ChannelAccounts;

use App\Enums\Channel;
use App\Exceptions\MetaApiException;
use App\Filament\Admin\Resources\ChannelAccounts\Pages\ManageChannelAccounts;
use App\Models\ChannelAccount;
use App\Services\Channels\MetaSocialGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ChannelAccountResource extends Resource
{
    protected static ?string $model = ChannelAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'cuenta de mensajería';

    protected static ?string $pluralModelLabel = 'canales de mensajería';

    protected static ?string $navigationLabel = 'Canales de mensajería';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dónde pegar en Meta')
                    ->description('Cada agencia usa su propia app de Meta: estas son las dos direcciones y la clave que Meta te pide al registrar el webhook.')
                    ->schema([
                        Placeholder::make('webhook_url')
                            ->label('URL de devolución de llamada (Instagram y Messenger)')
                            ->content(fn () => url('/webhooks/meta'.(filled(config('services.meta.webhook_token')) ? '/'.config('services.meta.webhook_token') : ''))),
                        Placeholder::make('whatsapp_webhook_url')
                            ->label('URL de devolución de llamada (WhatsApp)')
                            ->content(fn () => url('/webhooks/whatsapp')),
                    ])
                    ->columnSpanFull(),
                Section::make('Antes de conectar: qué tiene que tener la agencia en Meta')
                    ->description('Se hace una sola vez, con la cuenta de Facebook que administra el negocio.')
                    ->schema([
                        Placeholder::make('meta_setup_steps')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<ol class="list-decimal space-y-1 pl-5 text-sm">'
                                .'<li>Portafolio comercial de Meta (idealmente con la empresa verificada).</li>'
                                .'<li>App de tipo «Business» con los productos WhatsApp, Messenger e Instagram, en modo <b>Live</b>.</li>'
                                .'<li>WhatsApp: un número que no esté en la app común y un token permanente de «usuario del sistema» con <code>whatsapp_business_messaging</code> y <code>whatsapp_business_management</code>.</li>'
                                .'<li>Facebook e Instagram: la página vinculada a la cuenta profesional de Instagram, con <code>pages_messaging</code>, <code>instagram_manage_messages</code> y <code>pages_manage_metadata</code>.</li>'
                                .'<li>Pegar las URL de arriba en el webhook de cada producto, suscribir la página y activar el evento <code>messaging_postbacks</code>.</li>'
                                .'<li>Cargar abajo cada cuenta, probar la conexión y usar «Preguntas de entrada» en Instagram y Messenger.</li>'
                                .'</ol>'
                            )),
                    ])
                    ->collapsed()
                    ->columnSpanFull(),
                Select::make('channel')
                    ->label('Canal')
                    ->options(collect(Channel::inbox())->mapWithKeys(fn (Channel $c) => [$c->value => $c->getLabel()])->all())
                    ->required()
                    ->live(),
                TextInput::make('label')
                    ->label('Nombre interno')
                    ->helperText('Cómo la ves en la bandeja: «Ventas», «Soporte», «Instagram de la agencia».')
                    ->required(),
                TextInput::make('external_id')
                    ->label(fn ($get) => match ($get('channel')) {
                        'whatsapp' => 'ID del número de teléfono (Phone number ID)',
                        'instagram' => 'ID de la cuenta de Instagram',
                        default => 'ID de la página de Facebook',
                    })
                    ->helperText('Lo encontrás en el panel de tu app de Meta. Es un número largo, no el teléfono.')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $get) => $rule->where('channel', $get('channel'))),
                Select::make('auth_type')
                    ->label('Cómo se conectó')
                    ->options([
                        'facebook_login' => 'Página de Facebook (Messenger, o Instagram vinculado a la Página)',
                        'instagram_login' => 'Inicio de sesión de Instagram (token de Instagram)',
                    ])
                    ->default('facebook_login')
                    ->helperText('Define a qué servidor de Meta se envían las respuestas. Con token de Instagram elegí la segunda.')
                    ->visible(fn ($get) => $get('channel') === 'instagram')
                    ->required(),
                TextInput::make('page_id')
                    ->label('ID de la página de Facebook (opcional)')
                    ->helperText('Solo si Instagram está vinculado a una Página y respondés por ella.')
                    ->visible(fn ($get) => in_array($get('channel'), ['instagram', 'messenger'], true)),
                Toggle::make('allow_human_agent')
                    ->label('Permitir que una persona responda hasta 7 días después')
                    ->helperText('Usa la etiqueta «Agente humano» de Meta. La IA nunca la usa. Activala solo si tu app tiene ese permiso.')
                    ->visible(fn ($get) => in_array($get('channel'), ['instagram', 'messenger'], true)),
                TextInput::make('access_token')
                    ->label('Token de acceso')
                    ->password()
                    ->revealable()
                    ->helperText('Se guarda cifrado. Dejalo vacío al editar para conservar el actual.')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state)),
                TextInput::make('app_secret')
                    ->label('Secreto de la app de Meta')
                    ->password()
                    ->revealable()
                    ->helperText('Sirve para comprobar que los mensajes vienen de Meta. Se guarda cifrado.')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state)),
                TextInput::make('verify_token')
                    ->label('Token de verificación del webhook')
                    ->helperText('Inventá una clave y pegala igual en Meta al registrar el webhook.')
                    ->required(),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('channel')->label('Canal')->badge(),
                TextColumn::make('label')->label('Nombre')->searchable(),
                TextColumn::make('external_id')->label('ID en Meta')->copyable(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'reconnect_required' ? 'Reconectar' : 'Conectada')
                    ->color(fn (string $state) => $state === 'reconnect_required' ? 'danger' : 'success')
                    ->tooltip(fn (ChannelAccount $record) => $record->last_error),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([
                Action::make('entryPoints')
                    ->label('Preguntas de entrada')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->visible(fn (ChannelAccount $record) => $record->channel->isSocial())
                    ->requiresConfirmation()
                    ->modalDescription('Publica en Meta las preguntas que ve el cliente al abrir el chat: «Quiero cotizar un viaje», «Salidas grupales», «Cruceros» y «Viaje a medida». Reemplaza las que haya.')
                    ->action(function (ChannelAccount $record) {
                        try {
                            app(MetaSocialGateway::class)->setEntryPoints($record);
                        } catch (MetaApiException $e) {
                            Notification::make()->title('Meta no las aceptó')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Preguntas de entrada publicadas')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChannelAccounts::route('/'),
        ];
    }
}
