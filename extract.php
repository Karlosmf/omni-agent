<?php

/**
 * Script para descomprimir newdeploy.zip en Hostinger
 */
$zipFile = 'newdeploy.zip';

if (! file_exists($zipFile)) {
    exit("Error: No se encontró el archivo $zipFile en este directorio.");
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    // Extraer en el directorio actual
    if ($zip->extractTo(__DIR__)) {
        echo '<h1>Éxito</h1>';
        echo '<p>Archivos extraídos correctamente en: '.__DIR__.'</p>';
        echo '<p><strong>Siguiente paso:</strong> Configura tu archivo .env y ejecuta composer install desde la terminal SSH.</p>';
    } else {
        echo '<h1>Error</h1>';
        echo '<p>No se pudo extraer el contenido. Revisa los permisos de carpeta.</p>';
    }
    $zip->close();
} else {
    echo '<h1>Error</h1>';
    echo "<p>No se pudo abrir el archivo $zipFile.</p>";
}
