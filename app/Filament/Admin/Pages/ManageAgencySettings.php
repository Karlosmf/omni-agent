<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AiProvider;
use App\Models\AgencySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->action('save'),
        ];
    }

    public function mount(): void
    {
        $settings = AgencySetting::first();

        if ($settings) {
            $data = $settings->toArray();
            $data['is_maintenance_mode'] = app()->isDownForMaintenance();

            if (Storage::disk('local')->exists('agency_legal.json')) {
                $legalData = json_decode(Storage::disk('local')->get('agency_legal.json'), true);
                $data['cuit'] = $legalData['cuit'] ?? '';
                $data['legajo'] = $legalData['legajo'] ?? '';
            }

            $this->form->fill($data);
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
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('Nombre de la Empresa')
                                                    ->required(),
                                                TextInput::make('ai_assistant_name')
                                                    ->label('Nombre de la Asistente Virtual')
                                                    ->placeholder('Ej: Brisa')
                                                    ->required(),
                                                ToggleButtons::make('ai_provider')
                                                    ->label('Proveedor de IA')
                                                    ->options(AiProvider::class)
                                                    ->default(AiProvider::None)
                                                    ->inline()
                                                    ->live()
                                                    ->columnSpanFull(),
                                                TextInput::make('ai_api_key')
                                                    ->label('API Key de IA')
                                                    ->placeholder(function (Get $get): string {
                                                        $val = $get('ai_provider');
                                                        $provider = $val instanceof AiProvider ? $val : AiProvider::tryFrom($val ?? 'none');

                                                        return $provider?->getApiKeyPlaceholder() ?? 'API Key...';
                                                    })
                                                    ->helperText(function (Get $get): string {
                                                        $val = $get('ai_provider');
                                                        $provider = $val instanceof AiProvider ? $val : AiProvider::tryFrom($val ?? 'none');

                                                        return $provider?->getHelperText() ?? '';
                                                    })
                                                    ->password()
                                                    ->revealable()
                                                    ->visible(function (Get $get): bool {
                                                        $val = $get('ai_provider');
                                                        $val = $val instanceof AiProvider ? $val->value : ($val ?? 'none');

                                                        return $val !== 'none';
                                                    })
                                                    ->columnSpanFull(),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('logotipo_path')
                                                    ->label('Logotipo de la Agencia (Logo completo)')
                                                    ->image()
                                                    ->disk('branding')
                                                    ->directory('')
                                                    ->saveUploadedFileUsing(function (UploadedFile $file): string {
                                                        $manager = new ImageManager(new Driver);
                                                        $image = $manager->decode($file->getRealPath());
                                                        $encoded = $image->encode(new WebpEncoder(90));
                                                        Storage::disk('branding')->put('logotipo.webp', (string) $encoded);

                                                        return 'logotipo.webp';
                                                    })
                                                    ->maxSize(2048)
                                                    ->imageEditor()
                                                    ->imagePreviewHeight('80')
                                                    ->columnSpan(1),
                                                FileUpload::make('isotipo_path')
                                                    ->label('Isotipo de la Agencia (Icono/Símbolo - Se usará como Favicon)')
                                                    ->image()
                                                    ->disk('branding')
                                                    ->directory('')
                                                    ->saveUploadedFileUsing(function (UploadedFile $file): string {
                                                        $manager = new ImageManager(new Driver);
                                                        $image = $manager->decode($file->getRealPath());
                                                        $encoded = $image->encode(new WebpEncoder(90));
                                                        Storage::disk('branding')->put('isotipo.webp', (string) $encoded);

                                                        return 'isotipo.webp';
                                                    })
                                                    ->maxSize(1024)
                                                    ->imageEditor()
                                                    ->imagePreviewHeight('80')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                                Section::make('SEO y Metadatos')
                                    ->schema([
                                        TextInput::make('meta_description')
                                            ->label('Descripción SEO (Meta)')
                                            ->placeholder('Ej: Tu mejor opción para viajar por el mundo...')
                                            ->columnSpanFull(),
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

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('cuit')
                                                    ->label('CUIT')
                                                    ->placeholder('Ej: 30-12345678-9'),
                                                TextInput::make('legajo')
                                                    ->label('Número de Legajo')
                                                    ->placeholder('Ej: 12345'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('hero_cta_url')
                                                    ->label('URL Botón Principal (Hero)')
                                                    ->placeholder('Ej: https://tupagina.com/planear'),
                                                TextInput::make('google_maps_url')
                                                    ->label('URL Google Maps (Ubicación)')
                                                    ->placeholder('Ej: https://maps.app.goo.gl/...'),
                                            ]),

                                        TextInput::make('footer_text')
                                            ->label('Texto adicional en Footer')
                                            ->placeholder('Ej: Gracias por confiar en nosotros.')
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

                        Tab::make('Analítica y Scripts')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Section::make('Scripts Externos')
                                    ->description('Inserta códigos de seguimiento (Google Analytics, Meta Pixel, etc.)')
                                    ->schema([
                                        Textarea::make('header_scripts')
                                            ->label('Scripts en <head>')
                                            ->helperText('Se insertará justo antes del cierre de </head>')
                                            ->rows(5),
                                        Textarea::make('footer_scripts')
                                            ->label('Scripts en <body>')
                                            ->helperText('Se insertará antes del cierre de </body>')
                                            ->rows(5),
                                    ]),
                            ]),
                        Tab::make('Legal y Acuerdos')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Plantilla de Acuerdo')
                                    ->description('Redacta el acuerdo de viaje de tu agencia. Usa las etiquetas [NOMBRE_CLIENTE], [DNI_CLIENTE], [EMAIL_CLIENTE], [TELEFONO_CLIENTE], [TOTAL_VIAJE], [DESTINO] y [FECHA_VIAJE] para que se reemplacen automáticamente con los datos reales al generar el PDF.')
                                    ->schema([
                                        RichEditor::make('contract_template')
                                            ->label('Acuerdo de Viaje (Plantilla)')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'h2',
                                                'h3',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'underline',
                                                'undo',
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Mantenimiento')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Modo Mantenimiento')
                                    ->description('Activa el modo mantenimiento para ocultar el sitio al público general.')
                                    ->schema([
                                        Toggle::make('is_maintenance_mode')
                                            ->label('Activar Modo Mantenimiento')
                                            ->helperText('Si se activa, los visitantes verán una pantalla de mantenimiento.')
                                            ->live(),
                                        TextInput::make('maintenance_bypass_key')
                                            ->label('Clave de Acceso (Bypass)')
                                            ->placeholder('Ej: secreto123')
                                            ->helperText('Permite el acceso a la web si se accede con ?bypass=CLAVE (Ej: https://tudominio.com/?bypass=secreto123). Deja en blanco si no deseas bypass por URL.')
                                            ->visible(fn (Get $get): bool => $get('is_maintenance_mode') === true),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $legalData = [
            'cuit' => $data['cuit'] ?? '',
            'legajo' => $data['legajo'] ?? '',
        ];
        Storage::disk('local')->put('agency_legal.json', json_encode($legalData));
        unset($data['cuit'], $data['legajo']);

        $settings = AgencySetting::first() ?: new AgencySetting;
        $settings->fill($data);
        $settings->save();

        Cache::forget('agency_settings');

        if ($settings->is_maintenance_mode) {
            $options = [];
            if (! empty($settings->maintenance_bypass_key)) {
                $options['--secret'] = $settings->maintenance_bypass_key;
            }
            Artisan::call('down', $options);
        } else {
            Artisan::call('up');
        }

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }
}
