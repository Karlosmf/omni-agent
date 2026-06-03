<?php

namespace App\Filament\Customer\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Customer\Resources\Bookings\Pages\ManageBookings;
use App\Models\Booking;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('customer_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('file_number')
                    ->label('Nº de Archivo'),
                TextInput::make('destination')
                    ->label('Destino'),
                TextInput::make('nights')
                    ->label('Noches'),
                TextInput::make('passengers')
                    ->label('Pasajeros'),
                DateTimePicker::make('travel_date')
                    ->label('Fecha de Viaje'),
                TextInput::make('total_sell')
                    ->label('Total')
                    ->prefix('$'),
                TextInput::make('currency')
                    ->label('Moneda'),
                Select::make('status')
                    ->options(BookingStatus::class)
                    ->label('Estado'),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_number')
                    ->label('Nº')
                    ->searchable(),
                TextColumn::make('destination')
                    ->label('Destino')
                    ->searchable(),
                TextColumn::make('travel_date')
                    ->label('Fecha de Viaje')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_sell')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBookings::route('/'),
        ];
    }
}
