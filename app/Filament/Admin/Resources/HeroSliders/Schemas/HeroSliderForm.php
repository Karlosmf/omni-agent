<?php

namespace App\Filament\Admin\Resources\HeroSliders\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class HeroSliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenido de la Diapositiva')
                    ->schema([
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
                        FileUpload::make('image_path')
                            ->label('Imagen de Fondo / Lateral')
                            ->image()
                            ->disk('public')
                            ->directory('sliders')
                            ->required(),
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
