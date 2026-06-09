<?php

namespace App\Filament\Admin\Pages;

use App\Models\JsonSlider;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;

class ManageSliders extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $title = 'Administrar Sliders';

    protected static ?string $navigationLabel = 'Sliders (JSON)';

    protected static \UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 110;

    protected string $view = 'filament.admin.pages.manage-sliders';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * Get all sliders from the directory.
     */
    public function getSliders(): Collection
    {
        return JsonSlider::all();
    }

    /**
     * Define the create action.
     */
    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Crear Slider')
            ->icon('heroicon-m-plus')
            ->form($this->getSliderFormSchema())
            ->action(function (array $data) {
                $name = kebab_case($data['name']);

                if (JsonSlider::find($name)) {
                    Notification::make()
                        ->title('Error al crear')
                        ->body("Ya existe un slider con el nombre: {$name}.json")
                        ->danger()
                        ->send();

                    return;
                }

                $slider = new JsonSlider(
                    name: $name,
                    description: $data['description'] ?? null,
                    slides: $data['slides'] ?? []
                );

                $slider->save();

                Notification::make()
                    ->title('Slider creado correctamente')
                    ->success()
                    ->send();
            });
    }

    /**
     * Define the edit action.
     */
    public function editAction(array $arguments = []): Action
    {
        return Action::make('edit')
            ->label('Editar')
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->size('sm')
            ->form($this->getSliderFormSchema())
            ->fillForm(function (array $arguments) {
                $slider = JsonSlider::find($arguments['name']);

                return [
                    'name' => $slider->name,
                    'description' => $slider->description,
                    'slides' => $slider->slides,
                ];
            })
            ->action(function (array $data, array $arguments) {
                $oldName = $arguments['name'];
                $newName = kebab_case($data['name']);

                if ($oldName !== $newName && JsonSlider::find($newName)) {
                    Notification::make()
                        ->title('Error al actualizar')
                        ->body("Ya existe otro slider con el nombre: {$newName}.json")
                        ->danger()
                        ->send();

                    return;
                }

                $slider = new JsonSlider(
                    name: $newName,
                    description: $data['description'] ?? null,
                    slides: $data['slides'] ?? []
                );

                $slider->save($oldName);

                Notification::make()
                    ->title('Slider actualizado correctamente')
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(array $arguments = []): Action
    {
        return Action::make('delete')
            ->label('Eliminar')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('¿Eliminar Slider?')
            ->modalDescription('Esta acción es irreversible y eliminará de forma permanente el archivo JSON del servidor.')
            ->modalSubmitActionLabel('Sí, eliminar')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (array $arguments) {
                JsonSlider::delete($arguments['name']);

                Notification::make()
                    ->title('Slider eliminado correctamente')
                    ->success()
                    ->send();
            });
    }

    /**
     * The form schema for both creating and editing sliders.
     */
    protected function getSliderFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre del Slider')
                ->required()
                ->regex('/^[a-z0-9-_]+$/i')
                ->helperText('Solo letras, números y guiones. Se convertirá automáticamente a kebab-case (ej. "home-hero").')
                ->placeholder('ej: home-hero'),

            TextInput::make('description')
                ->label('Descripción')
                ->placeholder('Para identificar dónde se utiliza este slider'),

            Repeater::make('slides')
                ->label('Diapositivas')
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->nullable(),

                    TextInput::make('subtitle')
                        ->label('Subtítulo')
                        ->nullable(),

                    Textarea::make('description')
                        ->label('Descripción / Copiar texto')
                        ->rows(2)
                        ->nullable(),

                    FileUpload::make('image_path')
                        ->label('Imagen')
                        ->image()
                        ->disk('public')
                        ->directory('sliders')
                        ->required(),

                    TextInput::make('cta_button_text')
                        ->label('Texto Botón Principal (CTA)')
                        ->placeholder('Ej: Ver Paquetes')
                        ->nullable(),

                    TextInput::make('cta_button_url')
                        ->label('Enlace Botón Principal (CTA)')
                        ->placeholder('Ej: /packages')
                        ->nullable(),

                    TextInput::make('sec_button_text')
                        ->label('Texto Botón Secundario')
                        ->placeholder('Ej: Más Información')
                        ->nullable(),

                    TextInput::make('sec_button_url')
                        ->label('Enlace Botón Secundario')
                        ->placeholder('Ej: /contact')
                        ->nullable(),
                ])
                ->columns(2)
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->reorderable()
                ->reorderableWithDragAndDrop()
                ->defaultItems(1)
                ->columnSpanFull(),
        ];
    }
}
