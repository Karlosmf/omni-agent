<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class MakeDeployZip extends Command
{
    protected $signature = 'deploy:zip {--name= : Nombre del archivo ZIP (default: deploy_YYYY_MM_DD.zip)}';

    protected $description = 'Genera un ZIP listo para subir a Hostinger (excluye vendor, node_modules, .git, .env, etc.)';

    /** @var array<string> */
    protected array $excludeDirs = [
        '.git',
        'node_modules',
        'vendor',
        '.gemini',
        '.vscode',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/app/private/livewire-tmp',
    ];

    /** @var array<string> */
    protected array $excludeFiles = [
        '.env',
        'database/database.sqlite',
    ];

    /** @var array<string> */
    protected array $excludePatterns = [
        '*.zip',
    ];

    public function handle(): int
    {
        $name = $this->option('name') ?? 'deploy_'.now()->format('Y_m_d').'.zip';

        if (! str_ends_with($name, '.zip')) {
            $name .= '.zip';
        }

        $zipPath = base_path($name);

        if (file_exists($zipPath)) {
            if (! $this->confirm("El archivo {$name} ya existe. ¿Sobreescribir?", true)) {
                $this->info('Cancelado.');

                return self::SUCCESS;
            }
            unlink($zipPath);
        }

        $this->info('🔧 Compilando frontend (npm run build)...');
        $buildResult = null;
        exec('cd '.base_path().' && npm run build 2>&1', $buildOutput, $buildResult);

        if ($buildResult !== 0) {
            $this->error('❌ Error al compilar frontend:');
            $this->line(implode("\n", $buildOutput));

            return self::FAILURE;
        }

        $this->info('✅ Frontend compilado.');
        $this->info("📦 Creando {$name}...");

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('❌ No se pudo crear el archivo ZIP.');

            return self::FAILURE;
        }

        $basePath = base_path();
        $fileCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($basePath.'/', '', $file->getPathname());

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
                $fileCount++;
            }
        }

        $zip->close();

        $size = round(filesize($zipPath) / 1024 / 1024, 1);

        $this->newLine();
        $this->info("✅ {$name} creado exitosamente");
        $this->info("   📁 {$fileCount} archivos | 📦 {$size} MB");
        $this->newLine();
        $this->line('📋 Pasos para subir a Hostinger:');
        $this->line('   1. Sube el ZIP al File Manager de Hostinger');
        $this->line('   2. Descomprimí en public_html/');
        $this->line('   3. Ejecutá: composer install --no-dev --optimize-autoloader');
        $this->line('   4. Ejecutá: php artisan migrate --force');
        $this->line('   5. Ejecutá: php artisan storage:link');

        return self::SUCCESS;
    }

    protected function shouldExclude(string $path): bool
    {
        foreach ($this->excludeDirs as $dir) {
            if (str_starts_with($path, $dir.'/') || $path === $dir) {
                return true;
            }
        }

        foreach ($this->excludeFiles as $file) {
            if ($path === $file) {
                return true;
            }
        }

        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, basename($path))) {
                return true;
            }
        }

        return false;
    }
}
