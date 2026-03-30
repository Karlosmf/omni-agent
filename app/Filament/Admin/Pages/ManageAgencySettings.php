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
                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('primary_color')
                                    ->label('Color Primario')
                                    ->default('#1a56db'),
                                ColorPicker::make('secondary_color')
                                    ->label('Color Secundario')
                                    ->default('#7e22ce'),
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
