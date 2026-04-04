<?php

namespace App\Filament\Admin\Pages;

use App\Models\AgencySetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class ManageAgencySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Configuración Marca Blanca';

    protected static ?string $navigationLabel = 'Marca Blanca';

    protected static \UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected string $view = 'filament.admin.pages.manage-agency-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AgencySetting::first();

        if ($settings) {
            $this->form->fill($settings->toArray());
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Identidad y Frontend')
                            ->icon('heroicon-o-computer-desktop')
                            ->schema([
                                Section::make('Identidad Visual')
                                    ->schema([
                                        TextInput::make('company_name')
                                            ->label('Nombre de la Empresa')
                                            ->required(),
                                        
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('logo_path')
                                                    ->label('Logo de la Agencia')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('agency')
                                                    ->imageEditor()
                                                    ->imagePreviewHeight('80')
                                                    ->replacesExistingFiles()
                                                    ->columnSpan(1),

                                                FileUpload::make('favicon_path')
                                                    ->label('Favicon')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('agency')
                                                    ->imageEditor()
                                                    ->imagePreviewHeight('80')
                                                    ->replacesExistingFiles()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                                Section::make('Paleta Frontend (Público)')
                                    ->description('Define los colores que verán tus clientes en la web.')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                ColorPicker::make('fe_primary_color')
                                                    ->label('Color Primario')
                                                    ->helperText('Botones y enlaces.')
                                                    ->default('#1a56db'),
                                                ColorPicker::make('fe_secondary_color')
                                                    ->label('Color Secundario')
                                                    ->helperText('Destacados y gradientes.')
                                                    ->default('#7e22ce'),
                                                ColorPicker::make('fe_accent_color')
                                                    ->label('Color de Acento')
                                                    ->default('#f59e0b'),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                ColorPicker::make('fe_base_100_color')
                                                    ->label('Fondo Principal')
                                                    ->default('#ffffff'),
                                                ColorPicker::make('fe_base_200_color')
                                                    ->label('Superficie (Cards)')
                                                    ->default('#f2f2f2'),
                                                ColorPicker::make('fe_base_content_color')
                                                    ->label('Texto Principal')
                                                    ->default('#1f2937'),
                                            ]),
                                        Grid::make(4)
                                            ->schema([
                                                ColorPicker::make('fe_success_color')
                                                    ->label('Éxito')
                                                    ->default('#36d399'),
                                                ColorPicker::make('fe_error_color')
                                                    ->label('Error')
                                                    ->default('#f87272'),
                                                ColorPicker::make('fe_warning_color')
                                                    ->label('Advertencia')
                                                    ->default('#fbbd23'),
                                                ColorPicker::make('fe_info_color')
                                                    ->label('Información')
                                                    ->default('#3abff8'),
                                            ]),
                                    ]),
                            ]),
                        
                        Tab::make('Panel de Control (Backend)')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Section::make('Paleta del Panel')
                                    ->description('Personaliza los colores del panel administrativo de Filament.')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                ColorPicker::make('be_primary_color')
                                                    ->label('Color Primario del Panel')
                                                    ->helperText('Afecta a la navegación, botones y acentos del panel.')
                                                    ->default('#f59e0b'),
                                                ColorPicker::make('be_gray_color')
                                                    ->label('Color Neutral (Gris)')
                                                    ->default('#71717a'),
                                                ColorPicker::make('be_info_color')
                                                    ->label('Información')
                                                    ->default('#3b82f6'),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                ColorPicker::make('be_success_color')
                                                    ->label('Éxito')
                                                    ->default('#22c55e'),
                                                ColorPicker::make('be_warning_color')
                                                    ->label('Advertencia')
                                                    ->default('#f59e0b'),
                                                ColorPicker::make('be_danger_color')
                                                    ->label('Peligro / Error')
                                                    ->default('#ef4444'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Contacto y Redes')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Datos de Contacto')
                                    ->schema([
                                        TextInput::make('contact_email')
                                            ->label('Email de Contacto')
                                            ->email()
                                            ->required(),
                                        TextInput::make('contact_phone')
                                            ->label('Teléfono de Contacto')
                                            ->tel()
                                            ->required(),
                                        TextInput::make('address')
                                            ->label('Dirección')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Redes Sociales')
                                    ->schema([
                                        Repeater::make('social_links')
                                            ->label('Links de Redes Sociales')
                                            ->schema([
                                                TextInput::make('platform')
                                                    ->label('Plataforma')
                                                    ->placeholder('Ej: Instagram, Facebook, WhatsApp')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Enlace o Valor')
                                                    ->placeholder('Ej: https://instagram.com/tuperfil o email@agencia.com')
                                                    ->required(),
                                                TextInput::make('icon')
                                                    ->label('Icono (Phosphor)')
                                                    ->placeholder('Ej: ph-instagram-logo')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = AgencySetting::first() ?: new AgencySetting();
        $settings->fill($data);
        $settings->save();

        Cache::forget('agency_settings');

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }
}
