<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use App\Services\AiConciergeService;
use App\Traits\GeneratesItineraryWithAi;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItineraryDaysRelationManager extends RelationManager
{
    use GeneratesItineraryWithAi;

    protected static string $relationship = 'itineraryDays';

    protected static ?string $title = 'Itinerario del Viaje';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('day_number')
                    ->default(fn (?RelationManager $livewire): int => ($livewire ? $livewire->getRelationship()->count() : 0) + 1),

                DatePicker::make('date')
                    ->label('Fecha')
                    ->native(false),

                TextInput::make('title')
                    ->label('Título del Día')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Descripción / Actividades')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('location')
                    ->label('Ubicación (Ciudad, País)')
                    ->maxLength(255),

                FileUpload::make('image_path')
                    ->label('Imagen destacada')
                    ->image()
                    ->directory('itineraries')
                    ->columnSpanFull(),

                Repeater::make('services')
                    ->label('Servicios Incluidos')
                    ->schema([
                        Select::make('type')
                            ->label('Tipo de Servicio')
                            ->options([
                                'flight' => 'Vuelo',
                                'hotel' => 'Alojamiento',
                                'transfer' => 'Traslado',
                                'tour' => 'Excursión',
                                'meal' => 'Comida',
                                'other' => 'Otro',
                            ])
                            ->required(),
                        TextInput::make('description')
                            ->label('Descripción corta')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->defaultItems(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('day_number')
            ->defaultSort('day_number')
            ->columns([
                TextColumn::make('day_number')
                    ->label('Día')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('generate_ai')
                    ->label('Generar con IA')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->form(self::itineraryAiForm())
                    ->action(function (array $data, AiConciergeService $aiService, RelationManager $livewire) {
                        $days = $aiService->generateItinerary($data['prompt']);
                        if (! empty($days)) {
                            $booking = $livewire->getOwnerRecord();
                            $currentCount = $booking->itineraryDays()->count();

                            foreach ($days as $index => $day) {
                                $booking->itineraryDays()->create([
                                    'day_number' => $currentCount + $index + 1,
                                    'title' => $day['title'] ?? ('Día '.($index + 1)),
                                    'description' => $day['description'] ?? '',
                                ]);
                            }

                            self::notifyItinerarySuccess();
                        } else {
                            self::notifyItineraryFailure();
                        }
                    }),
                CreateAction::make(),
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
}
