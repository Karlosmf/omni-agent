<?php

namespace App\Filament\Customer\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Customer\Resources\Bookings\Pages\ManageBookings;
use App\Models\Booking;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('customer_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
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
                \Filament\Actions\ViewAction::make(),
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
