<?php

namespace App\Filament\Admin\Resources\PackageImports\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PackageImportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('¿De dónde sacamos la información?')
                ->description('La IA arma un borrador sin inventar datos y vos lo revisás antes de publicar. Se elimina el nombre del operador de origen.')
                ->schema([
                    Radio::make('source_type')
                        ->label('Fuente')
                        ->options(['url' => 'Un link', 'text' => 'Texto pegado', 'pdf' => 'Un archivo PDF'])
                        ->default('url')
                        ->inline()
                        ->live()
                        ->required(),

                    TextInput::make('source_url')
                        ->label('Link de la oferta')
                        ->url()
                        ->placeholder('https://...')
                        ->visible(fn (Get $get) => $get('source_type') === 'url')
                        ->required(fn (Get $get) => $get('source_type') === 'url'),

                    Textarea::make('source_text')
                        ->label('Texto de la oferta')
                        ->rows(10)
                        ->minLength(80)
                        ->visible(fn (Get $get) => $get('source_type') === 'text')
                        ->required(fn (Get $get) => $get('source_type') === 'text'),

                    FileUpload::make('source_file')
                        ->label('PDF')
                        ->disk('local')
                        ->directory('package-imports')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->visible(fn (Get $get) => $get('source_type') === 'pdf')
                        ->required(fn (Get $get) => $get('source_type') === 'pdf'),

                    Toggle::make('use_source_images')
                        ->label('Tengo derecho a usar las imágenes de esta fuente')
                        ->helperText('Si lo activás, las fotos de la página original se ofrecen junto a las fotos con licencia libre. Si no, solo se buscan fotos reales con licencia libre (Unsplash, Pexels, Wikimedia).')
                        ->visible(fn (Get $get) => $get('source_type') === 'url'),
                ]),
        ]);
    }
}
