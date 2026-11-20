<?php

namespace App\Filament\Admin\Resources\ChatMenus;

use App\Enums\ChatMenuAction;
use App\Enums\ChatMenuSource;
use App\Filament\Admin\Resources\ChatMenus\Pages\ManageChatMenus;
use App\Models\ChatMenu;
use App\Support\ListMessage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * WhatsApp list menus: a keyword opens one, and each option continues with the bot, a fixed text or another menu.
 */
class ChatMenuResource extends Resource
{
    protected static ?string $model = ChatMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'menú del chat';

    protected static ?string $pluralModelLabel = 'menús del chat';

    protected static ?string $navigationLabel = 'Menús del chat';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->helperText('Se ve como título de la lista en WhatsApp.')
                    ->maxLength(ListMessage::MAX_ROW_TITLE)
                    ->required(),
                Textarea::make('keywords')
                    ->label('Palabras que lo abren')
                    ->helperText('Una por línea o separadas por comas (ej: destinos, paquetes, ofertas). Sin palabras, solo se abre desde otro menú. Solo funciona en WhatsApp y nunca mientras una persona atiende el chat.')
                    ->rows(2),
                Select::make('source')
                    ->label('Opciones')
                    ->options(ChatMenuSource::class)
                    ->default(ChatMenuSource::Destinations)
                    ->live()
                    ->required(),
                Repeater::make('options')
                    ->label('Opciones escritas a mano')
                    ->visible(fn (Get $get): bool => self::sourceOf($get) === ChatMenuSource::Options)
                    ->required(fn (Get $get): bool => self::sourceOf($get) === ChatMenuSource::Options)
                    ->maxItems(30)
                    ->addActionLabel('Agregar opción')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Opción')->maxLength(ListMessage::MAX_ROW_TITLE)->required(),
                        TextInput::make('description')->label('Detalle (opcional)')->maxLength(ListMessage::MAX_ROW_DESCRIPTION),
                    ]),
                TextInput::make('header')->label('Encabezado (opcional)')->maxLength(ListMessage::MAX_HEADER),
                Textarea::make('body')
                    ->label('Texto del mensaje')
                    ->maxLength(ListMessage::MAX_BODY)
                    ->rows(3)
                    ->required(),
                TextInput::make('button_text')
                    ->label('Texto del botón')
                    ->placeholder('Ver opciones')
                    ->maxLength(ListMessage::MAX_BUTTON),
                Select::make('on_select')
                    ->label('Cuando el cliente elige una opción')
                    ->options(ChatMenuAction::class)
                    ->default(ChatMenuAction::Bot)
                    ->helperText('Si elige un paquete, queda asociado a la consulta.')
                    ->live()
                    ->required(),
                Textarea::make('reply_text')
                    ->label('Texto de respuesta')
                    ->helperText('{opcion} se reemplaza por lo que eligió el cliente.')
                    ->visible(fn (Get $get): bool => self::actionOf($get) === ChatMenuAction::Reply)
                    ->required(fn (Get $get): bool => self::actionOf($get) === ChatMenuAction::Reply)
                    ->rows(3),
                Select::make('next_menu_id')
                    ->label('Menú que se abre')
                    ->helperText('Si este menú es de destinos y el siguiente de paquetes, se muestran solo los paquetes del destino elegido.')
                    ->relationship('nextMenu', 'name', fn (Builder $query, ?ChatMenu $record) => $query->when($record, fn (Builder $query) => $query->whereKeyNot($record->id)))
                    ->visible(fn (Get $get): bool => self::actionOf($get) === ChatMenuAction::Menu)
                    ->required(fn (Get $get): bool => self::actionOf($get) === ChatMenuAction::Menu),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('keywords')->label('Palabras')->limit(40),
                TextColumn::make('source')->label('Opciones')->badge(),
                TextColumn::make('on_select')->label('Al elegir')->badge(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChatMenus::route('/'),
        ];
    }

    private static function sourceOf(Get $get): ?ChatMenuSource
    {
        $source = $get('source');

        return $source instanceof ChatMenuSource ? $source : ChatMenuSource::tryFrom((string) $source);
    }

    private static function actionOf(Get $get): ?ChatMenuAction
    {
        $action = $get('on_select');

        return $action instanceof ChatMenuAction ? $action : ChatMenuAction::tryFrom((string) $action);
    }
}
