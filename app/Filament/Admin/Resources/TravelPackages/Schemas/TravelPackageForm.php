<?php

namespace App\Filament\Admin\Resources\TravelPackages\Schemas;

use App\Enums\Currency;
use App\Enums\PriceBasis;
use App\Enums\SingleSupplementType;
use App\Models\ServiceType;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
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
                Tabs::make('Idea de Viaje')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Idea Rápida')
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
                                            ->minValue(0)
                                            ->helperText('Usá 0 para viajes de un día (Full Day / Sin noche).'),
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
                                            ->options(PriceBasis::toOptions())
                                            ->default(PriceBasis::PorPersona->value)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                                $basis = PriceBasis::tryFrom($state ?? '');
                                                $set('price_basis_min', $basis?->minimumPassengers() ?? 1);
                                            })
                                            ->helperText('Define la ocupación mínima que se cobra al generar un presupuesto.'),
                                        Hidden::make('price_basis_min')
                                            ->default(1),
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
                                Textarea::make('summary')
                                    ->label('Breve Descripción')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->helperText('Breve descripción para la tarjeta del listado o para dar una idea general rápida.'),
                                FileUpload::make('cover_image')
                                    ->label('Imagen de Portada (Obligatoria)')
                                    ->required()
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
                            ]),

                        Tabs\Tab::make('Políticas y Precios')
                            ->schema([
                                Grid::make(2)->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('single_supplement_type')
                                                ->label('Tipo de Suplemento Single')
                                                ->options(SingleSupplementType::toOptions())
                                                ->live(),
                                            TextInput::make('single_supplement_amount')
                                                ->label('Monto del Suplemento')
                                                ->numeric()
                                                ->prefix(fn (Get $get) => $get('single_supplement_type') === SingleSupplementType::Percent->value ? '%' : '$')
                                                ->visible(fn (Get $get) => filled($get('single_supplement_type'))),
                                        ])
                                        ->columnSpan(1),

                                    TextInput::make('triple_reduction_percent')
                                        ->label('Reducción Base Triple (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->columnSpan(1)
                                        ->helperText('Porcentaje de descuento por persona cuando ocupan base triple.'),
                                ]),

                                Repeater::make('children_policies')
                                    ->label('Políticas de Menores')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('max_age')
                                                ->label('Edad máxima (inclusive)')
                                                ->numeric()
                                                ->required(),
                                            Select::make('type')
                                                ->label('Tipo de tarifa')
                                                ->options([
                                                    'free' => 'Gratis (No paga)',
                                                    'percent' => 'Porcentaje sobre tarifa',
                                                    'fixed' => 'Precio Fijo',
                                                ])
                                                ->required()
                                                ->live(),
                                            TextInput::make('value')
                                                ->label('Valor (% o $)')
                                                ->numeric()
                                                ->visible(fn (Get $get) => $get('type') !== 'free')
                                                ->required(fn (Get $get) => $get('type') !== 'free'),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => 'Hasta '.($state['max_age'] ?? '?').' años: '.match ($state['type'] ?? '') {
                                        'free' => 'Gratis',
                                        'percent' => ($state['value'] ?? '?').'% de tarifa',
                                        'fixed' => '$'.($state['value'] ?? '?'),
                                        default => ''
                                    })
                                    ->defaultItems(0)
                                    ->addActionLabel('Agregar política de menores')
                                    ->collapsible(),

                                Repeater::make('seasons')
                                    ->label('Precios por Temporada')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('name')
                                                ->label('Nombre de la temporada')
                                                ->placeholder('Ej: Temporada Alta')
                                                ->required(),
                                            DatePicker::make('from')
                                                ->label('Desde')
                                                ->required(),
                                            DatePicker::make('to')
                                                ->label('Hasta')
                                                ->required(),
                                            TextInput::make('price_from')
                                                ->label('Precio Base')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required(),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => ($state['name'] ?? '').' ($'.($state['price_from'] ?? '?').')')
                                    ->defaultItems(0)
                                    ->addActionLabel('Agregar temporada')
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Descripción Extendida')
                            ->schema([
                                Textarea::make('description')
                                    ->label('Descripción Completa')
                                    ->rows(6),
                            ]),

                        Tabs\Tab::make('Itinerario')
                            ->schema([
                                \Filament\Forms\Components\Actions::make([
                                    \Filament\Forms\Components\Actions\Action::make('generate_itinerary')
                                        ->label('Generar Borrador con IA')
                                        ->icon('heroicon-o-sparkles')
                                        ->color('primary')
                                        ->form([
                                            \Filament\Forms\Components\Textarea::make('prompt')
                                                ->label('Instrucción para la IA')
                                                ->placeholder('Ej: Generame 7 días en Roma visitando lo más importante. Empezá por el Coliseo.')
                                                ->required(),
                                        ])
                                        ->action(function (array $data, Set $set, \App\Services\AiConciergeService $aiService) {
                                            $days = $aiService->generateItinerary($data['prompt']);
                                            if (!empty($days)) {
                                                $formatted = array_map(function($day) {
                                                    return [
                                                        'day' => 'Día ' . ($day['day'] ?? ''),
                                                        'title' => $day['title'] ?? '',
                                                        'description' => $day['description'] ?? '',
                                                    ];
                                                }, $days);
                                                $set('itinerary', $formatted);
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Itinerario generado con éxito')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Error al generar. Intenta de nuevo.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ]),
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

                        Tabs\Tab::make('Costos y Servicios')
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
                                                Select::make('price_basis')
                                                    ->label('Base del precio')
                                                    ->options(PriceBasis::toServiceOptions())
                                                    ->default(PriceBasis::PorPersona->value)
                                                    ->required()
                                                    ->columnSpan(2)
                                                    ->helperText('Por persona: vuelos, bus. Precio fijo: lancha, guía, show. Base doble/triple: habitaciones.'),

                                                Select::make('supplier_id')
                                                    ->label('Proveedor Sugerido')
                                                    ->options(Supplier::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
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

                        Tabs\Tab::make('Imágenes')
                            ->schema([
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

                        Tabs\Tab::make('Incluye / No Incluye')
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
                    ]), // Closes tabs()
            ]); // Closes components()
    }
}
