<?php

namespace App\Filament\Customer\Resources\Leads;

use App\Enums\LeadStatus;
use App\Filament\Customer\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\ViewAction;
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

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

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
                Select::make('travel_package_id')
                    ->relationship('travelPackage', 'title')
                    ->label('Paquete de Interés'),
                TextInput::make('source')
                    ->label('Origen')
                    ->required(),
                Select::make('status')
                    ->options(LeadStatus::class)
                    ->label('Estado')
                    ->required(),
                TextInput::make('customer_budget')
                    ->label('Presupuesto Estimado'),
                Textarea::make('raw_message')
                    ->label('Tu Mensaje Original')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('ai_summary')
                    ->label('Resumen del Asesor')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('travelPackage.title')
                    ->label('Paquete')
                    ->searchable(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->searchable(),
                TextColumn::make('customer_budget')
                    ->label('Presupuesto')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ManageLeads::route('/'),
        ];
    }
}
