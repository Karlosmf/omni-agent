<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use ZipArchive;

use function Laravel\Prompts\select;

class MakeDeployZip extends Command
{
    protected $signature = 'make:deploy-zip {--name=deploy.zip} {--no_brand} {--include-assets}';

    protected $description = 'Genera un paquete ZIP para despliegue detectando el mejor método (7-Zip o Nativo)';

    public function handle(): int
    {
        $noBrand = $this->option('no_brand');
        $includeAssets = $this->option('include-assets');
        $zipName = $this->option('name');
        $zipPath = base_path($zipName);

        // Si no se especificó la opción en la línea de comando y la sesión es interactiva,
        // preguntamos mediante el Prompt Select con valor por defecto false (no incluir).
        if (! $includeAssets && $this->input->isInteractive()) {
            $choice = select(
                label: '¿Deseas incluir los assets publicados de Livewire en el paquete?',
                options: [
                    'no' => 'No incluir assets (Recomendado)',
                    'yes' => 'Sí, incluir assets',
                ],
                default: 'no'
            );
            $includeAssets = ($choice === 'yes');
        }

        $this->info('🚀 Iniciando proceso de empaquetado unificado...');

        if ($noBrand) {
            $this->info('🚫 Opción --no_brand activa: se excluirán imágenes de marca y sliders.');
        }

        // 1. Preparación de Assets
        $this->info('📦 Ejecutando npm run build...');
        $process = new Process(['npm', 'run', 'build']);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error en npm run build.');

            return 1;
        }

        $this->info('📡 Publicando assets de Filament...');
        $this->call('filament:assets');

        if ($includeAssets) {
            $this->info('📡 Publicando assets de Livewire...');
            $this->call('livewire:publish', ['--assets' => true]);
        } else {
            $this->info('⏩ Omitiendo publicación de assets.');
        }

        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        // 2. Conteo de archivos a comprimir
        $filesCount = $this->getFilesCount($noBrand, basename($zipPath));
        $this->info("📂 Se han detectado {$filesCount} archivos elegibles para comprimir.");

        // 3. Detección de 7-Zip
        $sevenZipPath = $this->getSevenZipPath();

        if ($sevenZipPath) {
            return $this->useSevenZip($sevenZipPath, $zipPath, $noBrand);
        }

        return $this->useNativeZip($zipPath, $noBrand);
    }

    /**
     * Determina si un archivo debe ser excluido del paquete de despliegue.
     */
    private function shouldExclude(string $zipPathInternal, bool $noBrand): bool
    {
        if (Str::startsWith($zipPathInternal, 'vendor/') ||
            Str::startsWith($zipPathInternal, 'node_modules/') ||
            Str::startsWith($zipPathInternal, '.git/') ||
            Str::startsWith($zipPathInternal, 'storage/logs/') ||
            Str::startsWith($zipPathInternal, 'storage/framework/') ||
            Str::startsWith($zipPathInternal, 'storage/app/private/') ||
            Str::startsWith($zipPathInternal, 'storage/app/livewire-tmp/') ||
            Str::startsWith($zipPathInternal, 'bootstrap/cache/') ||
            Str::startsWith($zipPathInternal, 'public/storage') ||
            Str::endsWith($zipPathInternal, '.zip') ||
            Str::endsWith($zipPathInternal, '.sql') ||
            Str::endsWith($zipPathInternal, '.sqlite') ||
            Str::startsWith($zipPathInternal, '.agents') ||
            Str::startsWith($zipPathInternal, '.claude') ||
            Str::startsWith($zipPathInternal, '.gemini') ||
            Str::startsWith($zipPathInternal, '.vscode') ||
            Str::startsWith($zipPathInternal, '.postman') ||
            basename($zipPathInternal) === '.DS_Store' ||
            basename($zipPathInternal) === 'Thumbs.db' ||
            $zipPathInternal === '.env'
        ) {
            // Permitir public/vendor
            if (! Str::startsWith($zipPathInternal, 'public/vendor/')) {
                return true;
            }
        }

        if ($noBrand && Str::startsWith($zipPathInternal, 'public/imgs/')) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene el número total de archivos elegibles para comprimir.
     */
    private function getFilesCount(bool $noBrand, string $zipName): int
    {
        $rootPath = realpath(base_path());
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($files as $name => $file) {
            $filePath = $file->getRealPath();
            $relativePath = ltrim(str_replace($rootPath, '', $filePath), DIRECTORY_SEPARATOR);
            $zipPathInternal = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if ($this->shouldExclude($zipPathInternal, $noBrand)) {
                continue;
            }

            if ($zipPathInternal === $zipName || Str::startsWith($zipPathInternal, 'temp_')) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function getSevenZipPath(): ?string
    {
        // 1. Intentar ruta estándar de Windows
        $winPath = 'C:\\Program Files\\7-Zip\\7z.exe';
        if (PHP_OS_FAMILY === 'Windows' && file_exists($winPath)) {
            return $winPath;
        }

        // 2. Intentar buscar en el PATH (Linux o Windows con PATH configurado)
        $command = PHP_OS_FAMILY === 'Windows' ? 'where 7z' : 'which 7z';
        $process = Process::fromShellCommandline($command);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return null;
    }

    private function useSevenZip(string $binary, string $zipPath, bool $noBrand): int
    {
        $this->info("💎 Usando 7-Zip para máxima compatibilidad (Motor: $binary)");

        $exclusions = [
            'vendor', 'node_modules', '.git', '.env',
            'storage/logs/*', 'storage/framework/cache/data/*',
            'storage/framework/sessions/*', 'storage/framework/views/*',
            'storage/app/private/*', 'storage/app/livewire-tmp/*',
            'bootstrap/cache/*', 'public/storage',
            '*.zip', '*.sql', '*.sqlite',
            '.agents', '.claude', '.gemini', '.vscode', '.postman',
            '.DS_Store', 'Thumbs.db',
        ];

        if ($noBrand) {
            $exclusions[] = 'public/imgs/*';
        }

        $command = [$binary, 'a', '-tzip', $zipPath, '.'];
        foreach ($exclusions as $exclude) {
            $command[] = "-xr!$exclude";
        }

        $this->info('🤐 Comprimiendo...');
        $process = new Process($command, base_path());
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error al ejecutar 7-Zip.');

            return 1;
        }

        $this->info('✅ ¡Éxito! Paquete generado con 7-Zip: '.basename($zipPath));

        return 0;
    }

    private function useNativeZip(string $zipPath, bool $noBrand): int
    {
        $this->warn('⚠️ 7-Zip no detectado. Usando librería nativa PHP ZipArchive.');

        $zipName = basename($zipPath);
        $tempZipName = 'temp_'.time().'_'.$zipName;
        $tempPath = base_path($tempZipName);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('❌ No se pudo crear el archivo ZIP.');

            return 1;
        }

        // Agregar carpetas esenciales vacías
        $essentialDirs = [
            'storage/app/public', 'storage/app/livewire-tmp',
            'storage/app/private', 'storage/app/private/livewire-tmp',
            'storage/framework/cache/data', 'storage/framework/sessions',
            'storage/framework/views', 'storage/logs', 'bootstrap/cache',
            'public/uploads', 'public/uploads/sliders', 'public/uploads/ideas', 'public/uploads/livewire-tmp',
        ];
        foreach ($essentialDirs as $dir) {
            $zip->addEmptyDir($dir);
        }

        $rootPath = realpath(base_path());
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($files as $name => $file) {
            $filePath = $file->getRealPath();
            $relativePath = ltrim(str_replace($rootPath, '', $filePath), DIRECTORY_SEPARATOR);
            $zipPathInternal = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if ($this->shouldExclude($zipPathInternal, $noBrand)) {
                continue;
            }

            if ($zipPathInternal === basename($zipPath) || $zipPathInternal === $tempZipName) {
                continue;
            }

            $zip->addFile($filePath, $zipPathInternal);
            if (method_exists($zip, 'setEncryptionName')) {
                $zip->setEncryptionName($zipPathInternal, ZipArchive::EM_NONE);
            }
            $count++;
        }

        $this->info("🤐 Comprimiendo {$count} archivos con ZipArchive...");

        try {
            $closed = $zip->close();
        } catch (\Exception $e) {
            $closed = false;
        }

        if (! $closed) {
            $this->error('❌ Error: Windows o un Antivirus bloqueó el cierre del archivo ZIP.');
            $this->line('💡 Intenta desactivar temporalmente el Antivirus o cerrar programas que usen la carpeta.');
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return 1;
        }

        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        if (! @rename($tempPath, $zipPath)) {
            $this->error("❌ No se pudo renombrar el archivo a {$zipName}, pero se creó como {$tempZipName}");

            return 1;
        }

        $this->info('✅ ¡Éxito! Paquete generado (Nativo): '.basename($zipPath));

        return 0;
    }
}
