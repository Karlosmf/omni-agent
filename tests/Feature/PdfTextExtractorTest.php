<?php

use App\Enums\UserRole;
use App\Filament\Admin\Pages\PdfToTextTool;
use App\Models\User;
use App\Services\Import\PdfTextExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Builds a real PDF with dompdf and returns its path.
 */
function pdfWith(string $body, string $font = 'Helvetica'): string
{
    $html = '<html><body style="font-family: '.$font.'">'.$body.'</body></html>';
    $path = tempnam(sys_get_temp_dir(), 'pdf').'.pdf';
    file_put_contents($path, Pdf::loadHTML($html)->output());

    return $path;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/pdf*') ?: [] as $file) {
        @unlink($file);
    }
});

test('it reads the text of a PDF with the standard fonts', function () {
    $path = pdfWith('<h1>Gran Caribe 2027</h1><p>Salida 12 de marzo desde Asunción.</p><p>Tarifa desde USD 2.990 por pasajero.</p>');

    $text = (new PdfTextExtractor)->extract($path);

    expect($text)->toContain('Gran Caribe 2027')->toContain('Asunción')->toContain('USD 2.990');
});

test('it reads the text of a PDF with embedded subset fonts', function () {
    $path = pdfWith('<p>Camboriú, Año Nuevo en Río: 7 noches con desayuno.</p><p>Niños hasta 11 años: 50%.</p>', 'DejaVu Sans');

    $text = (new PdfTextExtractor)->extract($path);

    expect($text)->toContain('Camboriú')->toContain('Año Nuevo en Río')->toContain('Niños hasta 11 años');
});

test('lines and pages stay separated', function () {
    $path = pdfWith('<p>Primera línea del viaje.</p><p>Segunda línea del viaje.</p><div style="page-break-before: always"></div><p>Texto de la página dos.</p>');

    $text = (new PdfTextExtractor)->extract($path);

    expect($text)->toContain("Primera línea del viaje.\nSegunda línea del viaje.")
        ->and(strpos($text, 'Segunda línea'))->toBeLessThan(strpos($text, 'Texto de la página dos'));
});

test('a table keeps its cells apart', function () {
    $path = pdfWith('<table><tr><td>Hotel Capilla del Mar</td><td>USD 990</td></tr><tr><td>Riu Negril</td><td>USD 2.350</td></tr></table>');

    $text = (new PdfTextExtractor)->extract($path);

    expect($text)->toContain('Hotel Capilla del Mar')->toContain('USD 990')->toContain('Riu Negril')->toContain('USD 2.350');
});

test('a file that is not a PDF is rejected with a clear message', function () {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, 'Esto es solo texto, no un PDF'.str_repeat(' relleno', 20));

    expect(fn () => (new PdfTextExtractor)->extract($path))->toThrow(RuntimeException::class, 'no es un PDF');
});

test('a missing file is reported', function () {
    expect(fn () => (new PdfTextExtractor)->extract('/no/existe.pdf'))->toThrow(RuntimeException::class, 'No se pudo abrir');
});

test('a PDF without text asks for an AI that sees PDFs', function () {
    $path = pdfWith('<div style="width:100px;height:100px;background:#ccc"></div>');

    expect(fn () => (new PdfTextExtractor)->extract($path))->toThrow(RuntimeException::class, 'imagen escaneada');
});

test('a password protected PDF is reported', function () {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Encrypt 5 0 R >>\n%%EOF");

    expect(fn () => (new PdfTextExtractor)->extract($path))->toThrow(RuntimeException::class, 'contraseña');
});

test('it reads text stored in compressed object streams and hex strings', function () {
    $content = 'BT /F1 12 Tf 72 700 Td <536F6C6F20766961206275732064657364652052656361716F6E71756973746120> Tj 0 -14 Td [(Sali) -20 (das) -300 (los jueves)] TJ ET';
    $objStm = "1 0 2 40 \n<< /Type /Catalog /Pages 3 0 R >>\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $first = strlen("1 0 2 40 \n");
    $objects = [
        "4 0 obj\n<< /Type /ObjStm /N 2 /First {$first} /Filter /FlateDecode >>\nstream\n".gzcompress($objStm)."\nendstream\nendobj",
        "3 0 obj\n<< /Type /Pages /Kids [5 0 R] /Count 1 /Resources << /Font << /F1 2 0 R >> >> >>\nendobj",
        "5 0 obj\n<< /Type /Page /Parent 3 0 R /Contents 6 0 R >>\nendobj",
        "6 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream\nendobj",
    ];
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.5\n".implode("\n", $objects)."\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

    $text = (new PdfTextExtractor)->extract($path);

    expect($text)->toContain('Solo via bus desde Reca')->toContain('Salidas los jueves');
});

test('the artisan command prints the text and can write it to a file', function () {
    $path = pdfWith('<p>Colombia: Bogotá, Pereira y Medellín. Salida 10 de mayo.</p>');
    $out = tempnam(sys_get_temp_dir(), 'pdf').'.txt';

    $this->artisan('omni:pdf-to-text', ['file' => $path])->expectsOutputToContain('Bogotá, Pereira y Medellín')->assertSuccessful();
    $this->artisan('omni:pdf-to-text', ['file' => $path, '--output' => $out])->assertSuccessful();

    expect(file_get_contents($out))->toContain('Salida 10 de mayo');
    $this->artisan('omni:pdf-to-text', ['file' => '/no/existe.pdf'])->assertFailed();
});

test('the admin page converts an uploaded PDF and offers the text', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $path = pdfWith('<p>Ecuador Fascinante 7 días y 6 noches desde USD 2.443.</p>');
    $upload = UploadedFile::fake()->createWithContent('Oferta Ecuador.pdf', file_get_contents($path));
    $this->actingAs($user);

    $page = Livewire::test(PdfToTextTool::class)
        ->set('file', $upload)
        ->call('convert')
        ->assertHasNoErrors();

    expect($page->get('text'))->toContain('Ecuador Fascinante')->and($page->get('filename'))->toBe('Oferta Ecuador');
    $page->call('download')->assertFileDownloaded('oferta-ecuador.txt');
    $page->call('clear')->assertSet('text', '');
});

test('the admin page rejects files that are not PDFs and explains scanned PDFs', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($user);

    Livewire::test(PdfToTextTool::class)
        ->set('file', UploadedFile::fake()->create('foto.png', 10, 'image/png'))
        ->call('convert')
        ->assertHasErrors('file');

    $scan = file_get_contents(pdfWith('<div style="width:80px;height:80px;background:#ddd"></div>'));

    Livewire::test(PdfToTextTool::class)
        ->set('file', UploadedFile::fake()->createWithContent('scan.pdf', $scan))
        ->call('convert')
        ->assertNotified('No se pudo convertir')
        ->assertSet('text', '');
});
