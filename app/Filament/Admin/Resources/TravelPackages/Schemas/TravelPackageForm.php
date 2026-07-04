<?php

namespace App\Filament\Admin\Resources\TravelPackages\Schemas;

use App\Enums\Currency;
use App\Models\ServiceType;
use App\Models\Supplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class TravelPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Título')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                                TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('destination')
                                    ->label('Destino')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextInput::make('nights')
                                    ->label('Noches')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                                Select::make('currency')
                                    ->label('Moneda')
                                    ->options(Currency::class)
                                    ->default(Currency::USD->value)
                                    ->required(),
                                TextInput::make('price_from')
                                    ->label('Precio Desde')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Select::make('price_basis')
                                    ->label('Base del Precio')
                                    ->options([
                                        'por persona' => 'Por persona',
                                        'en base doble' => 'En base doble',
                                        'por persona, en base doble' => 'Por persona, en base doble',
                                    ])
                                    ->default('por persona')
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Select $component, ?\Illuminate\Database\Eloquent\Model $record) {
                                        if ($record && $record->exists) {
                                            $extras = \Illuminate\Support\Facades\Storage::disk('local')->exists('travel_packages_extras.json') ? json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get('travel_packages_extras.json'), true) : [];
                                            $component->state($extras[$record->id]['price_basis'] ?? 'por persona');
                                        } else {
                                            $component->state('por persona');
                                        }
                                    })
                                    ->saveRelationshipsUsing(function (Select $component, $state, ?\Illuminate\Database\Eloquent\Model $record) {
                                        if ($record) {
                                            $extras = \Illuminate\Support\Facades\Storage::disk('local')->exists('travel_packages_extras.json') ? json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get('travel_packages_extras.json'), true) : [];
                                            if (!isset($extras[$record->id])) {
                                                $extras[$record->id] = [];
                                            }
                                            $extras[$record->id]['price_basis'] = $state;
                                            \Illuminate\Support\Facades\Storage::disk('local')->put('travel_packages_extras.json', json_encode($extras));
                                        }
                                    }),
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ]),
                        TagsInput::make('tags')
                            ->label('Tags / Etiquetas')
                            ->placeholder('Agregar tag...')
                            ->suggestions([
                                'playa',
                                'aventura',
                                'familiar',
                                'all-inclusive',
                                'cultural',
                                'crucero',
                                'luna-de-miel',
                                'exótico',
                            ]),
                    ]),

                Section::make('Contenido')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('summary')
                            ->label('Resumen Corto')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Breve descripción para la tarjeta del listado.'),
                        Textarea::make('description')
                            ->label('Descripción Completa')
                            ->rows(6),
                    ]),

                Section::make('Itinerario')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('itinerary')
                            ->label('Días del viaje')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('day')
                                            ->label('Día')
                                            ->placeholder('Día 1')
                                            ->required(),
                                        TextInput::make('title')
                                            ->label('Título')
                                            ->placeholder('Llegada y traslado')
                                            ->required()
                                            ->columnSpan(2),
                                    ]),
                                Textarea::make('description')
                                    ->label('Descripción del día')
                                    ->rows(3),
                            ])
                            ->columns(1)
                            ->itemLabel(fn (array $state): ?string => ($state['day'] ?? '').': '.($state['title'] ?? ''))
                            ->addActionLabel('Agregar día')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Section::make('Detalle de Servicios Sugeridos')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('services')
                            ->label('Servicios Incluidos en la Idea')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('service_type_id')
                                            ->label('Tipo de Servicio')
                                            ->options(ServiceType::pluck('name', 'id'))
                                            ->required(),
                                        TextInput::make('description')
                                            ->label('Descripción / Detalle')
                                            ->required()
                                            ->columnSpan(3),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        Select::make('supplier_id')
                                            ->label('Proveedor Sugerido')
                                            ->options(Supplier::pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),

                                        Select::make('currency')
                                            ->label('Moneda')
                                            ->options(Currency::class)
                                            ->default(Currency::USD->value)
                                            ->required(),

                                        TextInput::make('cost')
                                            ->label('Costo Neto')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('sell')
                                            ->label('Precio Venta')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),
                                    ]),
                            ])
                            ->columns(1)
                            ->itemLabel(function (array $state): ?string {
                                $typeId = $state['service_type_id'] ?? null;
                                $serviceType = ServiceType::find($typeId);
                                $type = $serviceType ? $serviceType->name : 'N/A';

                                return $type.': '.($state['description'] ?? '');
                            })
                            ->addActionLabel('Agregar Servicio')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Section::make('Imágenes')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Imagen de Portada')
                            ->image()
                            ->disk('uploads')
                            ->saveUploadedFileUsing(function (UploadedFile $file): string {
                                $manager = new ImageManager(new Driver);
                                $image = $manager->decode($file->getRealPath());
                                $encoded = $image->encode(new WebpEncoder(90));
                                $filename = Str::random(40).'.webp';

                                Storage::disk('uploads')->put('ideas/'.$filename, (string) $encoded);

                                return 'ideas/'.$filename;
                            })
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('675'),
                        FileUpload::make('gallery')
                            ->label('Galería de Fotos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('uploads')
                            ->saveUploadedFileUsing(function (UploadedFile $file): string {
                                $manager = new ImageManager(new Driver);
                                $image = $manager->decode($file->getRealPath());
                                $encoded = $image->encode(new WebpEncoder(90));
                                $filename = Str::random(40).'.webp';

                                Storage::disk('uploads')->put('ideas/'.$filename, (string) $encoded);

                                return 'ideas/'.$filename;
                            })
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('800'),
                    ]),

                Section::make('Incluye / No incluye')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        Textarea::make('included')
                            ->label('¿Qué incluye?')
                            ->rows(4)
                            ->placeholder("✅ Aéreos ida y vuelta\n✅ Alojamiento\n✅ Traslados"),
                        Textarea::make('excluded')
                            ->label('¿Qué NO incluye?')
                            ->rows(4)
                            ->placeholder("❌ Excursiones opcionales\n❌ Comidas no mencionadas"),
                    ]),
            ]);
    }
}
