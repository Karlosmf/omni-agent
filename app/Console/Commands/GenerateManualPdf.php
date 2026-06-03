<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateManualPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-manual-pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convierte el archivo MANUAL_USUARIO.md en un archivo PDF utilizando DomPDF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mdPath = base_path('MANUAL_USUARIO.md');
        $pdfPath = public_path('MANUAL_USUARIO.pdf');

        if (!\Illuminate\Support\Facades\File::exists($mdPath)) {
            $this->error("El archivo $mdPath no existe.");
            return;
        }

        $this->info("Leyendo MANUAL_USUARIO.md...");
        $markdownContent = \Illuminate\Support\Facades\File::get($mdPath);

        $this->info("Convirtiendo Markdown a HTML y limpiando Emojis...");
        // Expresión regular para eliminar Emojis (ya que DomPDF no soporta Emojis a color y muestra rectángulos)
        $markdownContent = preg_replace('/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}]/u', '', $markdownContent);
        
        $htmlContent = \Illuminate\Support\Str::markdown($markdownContent);

        // Agregamos un poco de estilo básico para que el PDF se vea bien
        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: "DejaVu Sans", sans-serif; line-height: 1.5; color: #333; margin: 30px; }
                h1, h2, h3 { color: #111; margin-top: 20px; }
                h1 { border-bottom: 2px solid #ddd; padding-bottom: 10px; }
                h2 { border-bottom: 1px solid #eee; padding-bottom: 5px; }
                p, li { font-size: 14px; }
                code { background-color: #f4f4f4; padding: 2px 4px; border-radius: 4px; font-family: monospace; }
                pre { background-color: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
                hr { border: 0; border-top: 1px solid #ccc; margin: 20px 0; }
            </style>
        </head>
        <body>
            ' . $htmlContent . '
        </body>
        </html>';

        $this->info("Generando PDF (DomPDF)...");
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        
        $pdf->save($pdfPath);

        $this->info("¡PDF generado exitosamente (sin rectángulos) en: $pdfPath!");
    }
}
