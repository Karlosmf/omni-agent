<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class MakeVendorZip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:vendor-zip';

    protected $description = 'Crea un archivo ZIP de la carpeta vendor utilizando 7zip.';

    public function handle()
    {
        $this->info('Iniciando compresión de la carpeta vendor con 7zip...');

        $vendorPath = base_path('vendor');
        $zipPath = base_path('vendor.zip');

        if (! is_dir($vendorPath)) {
            $this->error("No se encontró la carpeta vendor en: {$vendorPath}");

            return self::FAILURE;
        }

        // Si ya existe un archivo zip viejo, lo eliminamos para evitar problemas
        if (file_exists($zipPath)) {
            $this->info('Eliminando archivo vendor.zip anterior...');
            unlink($zipPath);
        }

        // Ejecutar 7z (agregamos el flag -tzip para asegurar formato ZIP estándar)
        // 7z a -tzip vendor.zip vendor/
        $process = Process::path(base_path())->run([
            '7z', 'a', '-tzip', 'vendor.zip', 'vendor/',
        ]);

        // Si "7z" falla, intentamos "7za" que es otro nombre común para 7zip en macOS/Linux
        if (! $process->successful() && str_contains($process->errorOutput(), 'command not found')) {
            $this->info('Intentando con el comando alternativo "7za"...');
            $process = Process::path(base_path())->run([
                '7za', 'a', '-tzip', 'vendor.zip', 'vendor/',
            ]);
        }

        if ($process->successful()) {
            $this->info('✅ Archivo vendor.zip creado con éxito.');

            // Mostrar tamaño del archivo
            if (file_exists($zipPath)) {
                $size = round(filesize($zipPath) / 1024 / 1024, 2);
                $this->line("Tamaño final: {$size} MB");
            }

            return self::SUCCESS;
        }

        $this->error('Ocurrió un error al intentar crear el ZIP con 7zip:');
        $this->error($process->errorOutput() ?: $process->output());
        $this->line('Asegurate de tener 7zip instalado en tu sistema. (En macOS podés instalarlo con: brew install p7zip)');

        return self::FAILURE;
    }
}
