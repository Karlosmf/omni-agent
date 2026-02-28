<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class CreateDeployPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-deploy-package {--name=deploy.zip : El nombre del archivo ZIP de salida}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un archivo ZIP para deploy excluyendo vendor y archivos ocultos (dot files).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $zipName = $this->option('name');
        if (! str_ends_with($zipName, '.zip')) {
            $zipName .= '.zip';
        }

        $zipPath = base_path($zipName);

        if (file_exists($zipPath)) {
            $this->warn("El archivo {$zipName} ya existe.");
            if (! $this->confirm('¿Deseas sobrescribirlo?', true)) {
                $this->info('Operación cancelada.');

                return self::SUCCESS;
            }
            unlink($zipPath);
        }

        $this->info('Iniciando la creación del paquete de despliegue...');

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            $this->error('No se pudo crear el archivo ZIP.');

            return self::FAILURE;
        }

        $basePath = base_path();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($basePath) + 1);

            // Normalizar separadores para ZIP
            $relativePath = str_replace('\\', '/', $relativePath);

            // Excluir vendor
            if (str_starts_with($relativePath, 'vendor/')) {
                continue;
            }

            // Excluir node_modules (práctica recomendada aunque no se pida explícitamente)
            if (str_starts_with($relativePath, 'node_modules/')) {
                continue;
            }

            // Excluir archivos ocultos (dot files) y carpetas ocultas
            $parts = explode('/', $relativePath);
            $shouldExclude = false;
            foreach ($parts as $part) {
                if (str_starts_with($part, '.')) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            // También excluir el propio archivo zip que estamos creando si está en el root
            if ($relativePath === $zipName) {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
            $count++;

            if ($count % 100 === 0) {
                $this->output->write('.');
            }
        }

        $zip->close();

        $this->newLine();
        $this->info("✅ Paquete creado con éxito: {$zipName}");
        $this->info("Total de archivos incluidos: {$count}");

        return self::SUCCESS;
    }
}
