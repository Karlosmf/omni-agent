<?php

namespace App\Console\Commands;

use App\Services\Import\PdfTextExtractor;
use Illuminate\Console\Command;
use RuntimeException;

class PdfToText extends Command
{
    protected $signature = 'omni:pdf-to-text {file : Ruta del PDF} {--output= : Archivo .txt donde guardar el resultado (si no, se imprime)}';

    protected $description = 'Convierte un PDF en texto plano, para poder importarlo con proveedores de IA que no leen PDFs (como Groq).';

    public function handle(PdfTextExtractor $extractor): int
    {
        try {
            $text = $extractor->extract((string) $this->argument('file'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($output = $this->option('output')) {
            file_put_contents($output, $text);
            $this->info('Texto guardado en '.$output.' ('.mb_strlen($text).' caracteres).');

            return self::SUCCESS;
        }

        $this->line($text);

        return self::SUCCESS;
    }
}
