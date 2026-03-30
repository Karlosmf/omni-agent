<?php

namespace App\Filament\Admin\Pages;

use App\Models\AgencySetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

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
                Section::make('Identidad Visual')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nombre de la Empresa')
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('agency'),
                                FileUpload::make('favicon_path')
                                    ->label('Favicon')
                                    ->image()
                                    ->directory('agency'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                ColorPicker::make('primary_color')
                                    ->label('Color Primario')
                                    ->helperText('Botones principales y enlaces activos.')
                                    ->default('#1a56db'),
                                ColorPicker::make('secondary_color')
                                    ->label('Color Secundario')
                                    ->helperText('Elementos destacados y gradientes.')
                                    ->default('#7e22ce'),
                                ColorPicker::make('accent_color')
                                    ->label('Color de Acento')
                                    ->helperText('Detalles llamativos y micro-interacciones.')
                                    ->default('#f59e0b'),
                            ]),
                        Section::make('Paleta de Interfaz')
                            ->description('Colores que definen la estructura y el fondo de la aplicación.')
                            ->compact()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        ColorPicker::make('base_100_color')
                                            ->label('Fondo Principal (Páginas)')
                                            ->default('#ffffff'),
                                        ColorPicker::make('base_200_color')
                                            ->label('Superficie (Cards/Secciones)')
                                            ->default('#f2f2f2'),
                                        ColorPicker::make('base_content_color')
                                            ->label('Color de Texto Principal')
                                            ->default('#1f2937'),
                                    ]),
                            ]),
                        Section::make('Colores de Estado')
                            ->description('Colores utilizados para dar feedback al usuario (mensajes de éxito, error, etc).')
                            ->compact()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        ColorPicker::make('success_color')
                                            ->label('Éxito')
                                            ->default('#36d399'),
                                        ColorPicker::make('error_color')
                                            ->label('Error / Crítico')
                                            ->default('#f87272'),
                                        ColorPicker::make('warning_color')
                                            ->label('Advertencia')
                                            ->default('#fbbd23'),
                                        ColorPicker::make('info_color')
                                            ->label('Información')
                                            ->default('#3abff8'),
                                    ]),
                            ]),
                    ]),

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
                                    ->label('URL')
                                    ->url()
                                    ->required(),
                                TextInput::make('icon')
                                    ->label('Icono (Phosphor)')
                                    ->placeholder('Ej: ph-instagram-logo')
                                    ->helperText('Usa los nombres de https://phosphoricons.com (ej: ph-facebook-logo)')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
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
