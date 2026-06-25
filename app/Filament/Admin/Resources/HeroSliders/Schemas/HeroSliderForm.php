<?php

namespace App\Filament\Admin\Resources\HeroSliders\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class HeroSliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenido de la Diapositiva')
                    ->schema([
                        Select::make('slider_type')
                            ->label('Ubicación del Slider')
                            ->options([
                                'main' => 'Slider Principal (Pantalla completa)',
                                'hero_stack' => 'Hero Stack (Carrusel de fotos derecho)',
                                'promo' => 'Promociones (Banner debajo del principal)',
                            ])
                            ->default('main')
                            ->live()
                            ->required(),
                        TextInput::make('title')
                            ->label('Título Principal')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->label('Subtítulo / Etiqueta Superior')
                            ->placeholder('Ej: ✈️ Tu compañera de viajes')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),

                        Radio::make('image_type')
                            ->label('Tipo de Imagen')
                            ->options([
                                'upload' => 'Subir propia imagen',
                                'predefined' => 'Elegir imagen predefinida',
                                'url' => 'Enlace externo (URL)',
                            ])
                            ->default('upload')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Radio $component, $state, $record) {
                                if ($record && str_starts_with($record->image_path, 'predefined/')) {
                                    $component->state('predefined');
                                } elseif ($record && str_starts_with($record->image_path, 'http')) {
                                    $component->state('url');
                                } else {
                                    $component->state('upload');
                                }
                            }),

                        FileUpload::make('image_path')
                            ->label('Imagen de Fondo / Lateral')
                            ->image()
                            ->disk('uploads')
                            ->directory('sliders')
                            ->helperText(fn (Get $get) => match ($get('slider_type')) {
                                'main' => 'Resolución recomendada: 1920x1080px (Apaisada / Pantalla Completa)',
                                'hero_stack' => 'Resolución recomendada: 800x1000px (Vertical / Retrato)',
                                'promo' => 'Resolución recomendada: 1600x400px (Banner panorámico)',
                                default => 'Suba una imagen en alta calidad'
                            })
                            ->required(fn (Get $get) => $get('image_type') === 'upload')
                            ->visible(fn (Get $get) => $get('image_type') === 'upload'),

                        TextInput::make('image_path_url')
                            ->label('URL de la Imagen')
                            ->url()
                            ->helperText(fn (Get $get) => match ($get('slider_type')) {
                                'main' => 'Resolución recomendada: 1920x1080px (Apaisada / Pantalla Completa)',
                                'hero_stack' => 'Resolución recomendada: 800x1000px (Vertical / Retrato)',
                                'promo' => 'Resolución recomendada: 1600x400px (Banner panorámico)',
                                default => 'Use una URL de imagen en alta calidad'
                            })
                            ->required(fn (Get $get) => $get('image_type') === 'url')
                            ->visible(fn (Get $get) => $get('image_type') === 'url')
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                if ($record && str_starts_with($record->image_path, 'http')) {
                                    $component->state($record->image_path);
                                }
                            }),

                        Select::make('image_path_predefined')
                            ->label('Imagen Predefinida')
                            ->options([
                                'predefined/beach_resort.png' => 'Playa y Resort Tropical',
                                'predefined/city.png' => 'Ciudad Europea (París)',
                                'predefined/mountain.png' => 'Naturaleza y Montañas',
                            ])
                            ->required(fn (Get $get) => $get('image_type') === 'predefined')
                            ->visible(fn (Get $get) => $get('image_type') === 'predefined')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, $state, $record) {
                                if ($record && str_starts_with($record->image_path, 'predefined/')) {
                                    $component->state($record->image_path);
                                }
                            }),

                        Placeholder::make('image_preview')
                            ->label('Vista Previa')
                            ->content(function (Get $get) {
                                if ($get('image_type') === 'predefined') {
                                    $path = $get('image_path_predefined');
                                    if (! $path) {
                                        return 'Seleccione una imagen para ver la vista previa.';
                                    }

                                    return new HtmlString('<img src="'.asset('storage/'.$path).'" style="max-height: 200px; border-radius: 8px;">');
                                } elseif ($get('image_type') === 'url') {
                                    $url = $get('image_path_url');
                                    if (! $url) {
                                        return 'Ingrese una URL para ver la vista previa.';
                                    }

                                    return new HtmlString('<img src="'.$url.'" style="max-height: 200px; border-radius: 8px;" onerror="this.style.display=\'none\'">');
                                }

                                return '';
                            })
                            ->visible(fn (Get $get) => ($get('image_type') === 'predefined' && $get('image_path_predefined')) || ($get('image_type') === 'url' && $get('image_path_url'))),
                    ]),

                Section::make('Botones (Acciones)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cta_button_text')
                                    ->label('Texto Botón Principal')
                                    ->placeholder('Ej: Planear mi viaje'),
                                TextInput::make('cta_button_url')
                                    ->label('URL Botón Principal')
                                    ->placeholder('Ej: /contacto'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sec_button_text')
                                    ->label('Texto Botón Secundario')
                                    ->placeholder('Ej: Ver ubicación'),
                                TextInput::make('sec_button_url')
                                    ->label('URL Botón Secundario')
                                    ->placeholder('Ej: https://maps.google.com/...'),
                            ]),
                    ]),

                Section::make('Configuración')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('¿Activo?')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Orden de aparición')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
