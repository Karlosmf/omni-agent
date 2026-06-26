<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ServerAdmin extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static \UnitEnum|string|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Server Admin';

    protected static ?string $title = 'Server Administration';

    protected static ?string $slug = 'server-admin';

    protected string $view = 'filament.pages.server-admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDirectories')
                ->label('Create Missing Directories')
                ->icon('heroicon-o-folder-plus')
                ->action('createMissingDirectories')
                ->requiresConfirmation()
                ->color('primary'),

            Action::make('fixPermissions')
                ->label('Fix Permissions')
                ->icon('heroicon-o-wrench')
                ->form([
                    Select::make('permission')
                        ->label('Permission Level')
                        ->options([
                            '0755' => '0755 (Standard)',
                            '0775' => '0775 (Group Writable)',
                            '0777' => '0777 (World Writable - Not Recommended)',
                        ])
                        ->default('0755')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->fixPermissions($data['permission']);
                })
                ->color('warning'),

            Action::make('createStorageLink')
                ->label('Run Storage Link')
                ->icon('heroicon-o-link')
                ->action('createStorageLink')
                ->requiresConfirmation()
                ->color('success'),
        ];
    }

    public function createMissingDirectories()
    {
        $directoriesToCheck = ['images', 'uploads', 'assets', 'sliders'];
        $createdCount = 0;
        $errors = [];

        foreach ($directoriesToCheck as $dir) {
            $path = public_path($dir);
            if (! File::isDirectory($path)) {
                try {
                    $success = File::makeDirectory($path, 0755, true, true);
                    if ($success) {
                        $createdCount++;
                    } else {
                        $errors[] = "No se pudo crear $dir (Posible falta de permisos en public/)";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error al crear $dir: ".$e->getMessage();
                }
            }
        }

        if (count($errors) > 0) {
            Notification::make()
                ->title('Errores al crear directorios')
                ->body(implode('<br>', $errors))
                ->danger()
                ->send();
        }

        if ($createdCount > 0 || count($errors) === 0) {
            Notification::make()
                ->title("Se crearon $createdCount directorios faltantes.")
                ->success()
                ->send();
        }
    }

    public function fixPermissions(string $permissionMode = '0755')
    {
        $directoriesToCheck = ['images', 'uploads', 'assets', 'storage', 'sliders'];
        $fixedCount = 0;
        $errors = [];

        // Convert string octal to integer octal for chmod
        $octalMode = octdec($permissionMode);

        foreach ($directoriesToCheck as $dir) {
            $path = public_path($dir);
            if (File::isDirectory($path)) {
                try {
                    $success = @chmod($path, $octalMode);
                    if ($success) {
                        $fixedCount++;
                    } else {
                        $errorMsg = error_get_last()['message'] ?? 'Permiso denegado';
                        $errors[] = "No se pudo cambiar $dir: $errorMsg";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error en $dir: ".$e->getMessage();
                }
            }
        }

        if (count($errors) > 0) {
            Notification::make()
                ->title('Errores al cambiar permisos')
                ->body(implode('<br>', $errors))
                ->danger()
                ->send();
        }

        if ($fixedCount > 0 || count($errors) === 0) {
            Notification::make()->title("Se intentaron arreglar los permisos ($permissionMode) en $fixedCount directorios.")->success()->send();
        }
    }

    public function createStorageLink()
    {
        try {
            Artisan::call('storage:link');
            Notification::make()->title('Storage link created successfully.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed to create storage link.')->body($e->getMessage())->danger()->send();
        }
    }

    public function getDirectoriesProperty(): array
    {
        $directoriesToCheck = [
            'images',
            'uploads',
            'assets',
            'storage',
            'sliders',
        ];

        $results = [];

        foreach ($directoriesToCheck as $dir) {
            $path = public_path($dir);
            $exists = File::isDirectory($path);
            $isWritable = $exists ? is_writable($path) : false;
            $owner = $exists ? fileowner($path) : null;
            $permissions = $exists ? substr(sprintf('%o', fileperms($path)), -4) : null;

            if ($owner !== null && function_exists('posix_getpwuid')) {
                $ownerInfo = posix_getpwuid($owner);
                $ownerName = $ownerInfo['name'] ?? $owner;
            } else {
                $ownerName = $owner;
            }

            $results[] = [
                'name' => $dir,
                'path' => $path,
                'exists' => $exists,
                'writable' => $isWritable,
                'owner' => $ownerName,
                'permissions' => $permissions,
            ];
        }

        return $results;
    }
}
