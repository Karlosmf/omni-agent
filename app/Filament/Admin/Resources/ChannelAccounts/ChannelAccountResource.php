<?php

namespace App\Filament\Admin\Resources\ChannelAccounts;

use App\Enums\Channel;
use App\Filament\Admin\Resources\ChannelAccounts\Pages\ManageChannelAccounts;
use App\Models\ChannelAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([
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
