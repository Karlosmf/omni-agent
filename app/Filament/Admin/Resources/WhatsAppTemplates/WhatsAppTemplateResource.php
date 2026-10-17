<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplates;

use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\ManageWhatsAppTemplates;
use App\Models\WhatsAppTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'plantilla de WhatsApp';

    protected static ?string $pluralModelLabel = 'plantillas de WhatsApp';

    protected static ?string $navigationLabel = 'Plantillas de WhatsApp';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purpose')
                    ->label('Para qué se usa')
                    ->options([
                        'installment_reminder' => 'Recordatorio de cuota (variables: nombre, cuota, monto, vencimiento)',
                        'quote_ready' => 'Presupuesto listo',
                        'follow_up' => 'Seguimiento',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nombre exacto en Meta')
                    ->helperText('Tal cual lo aprobó Meta en el Administrador de WhatsApp (minúsculas y guiones bajos).')
                    ->required(),
                TextInput::make('language')->label('Idioma')->default('es_AR')->required(),
                Textarea::make('body')
                    ->label('Texto de la plantilla')
                    ->helperText('Usá {{1}}, {{2}}… como en Meta. Sirve para la vista previa en la bandeja.')
                    ->rows(4)
                    ->required(),
                TextInput::make('variables_count')->label('Cantidad de variables')->numeric()->integer()->minValue(0)->maxValue(10)->default(0)->required(),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purpose')->label('Uso')->badge(),
                TextColumn::make('name')->label('Nombre en Meta')->searchable(),
                TextColumn::make('variables_count')->label('Variables'),
                IconColumn::make('is_active')->label('Activa')->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageWhatsAppTemplates::route('/'),
        ];
    }
}
