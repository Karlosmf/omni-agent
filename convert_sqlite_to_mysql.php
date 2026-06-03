<?php

/**
 * Conversor Final de SQLite a MySQL
 * Implementa REPLACE INTO para evitar errores de duplicados.
 */
$inputFile = 'raw_dump.sql';
$outputFile = 'database_mysql_converted.sql';

if (! file_exists($inputFile)) {
    exit("Error: Genera el dump: sqlite3 database/database.sqlite .dump > raw_dump.sql\n");
}

$input = file_get_contents($inputFile);
$output = "-- MySQL Export Final\n";
$output .= "SET FOREIGN_KEY_CHECKS=0;\n";
$output .= "SET UNIQUE_CHECKS=0;\n";
$output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$output .= "SET NAMES utf8mb4;\n\n";

$lines = explode("\n", $input);
$processedLines = [];
$buffer = '';
$inCreate = false;

foreach ($lines as $line) {
    if (stripos($line, 'CREATE TABLE') === 0) {
        $inCreate = true;
        $buffer = $line;
    } elseif ($inCreate) {
        $buffer .= ' '.$line;
        if (str_ends_with(trim($line), ');') || str_ends_with(trim($line), ')')) {
            $processedLines[] = $buffer;
            $buffer = '';
            $inCreate = false;
        }
    } else {
        if (! empty(trim($line))) {
            $processedLines[] = $line;
        }
    }
}

foreach ($processedLines as $line) {
    $line = trim($line);
    if (preg_match('/^(PRAGMA|BEGIN TRANSACTION|COMMIT|CREATE INDEX|UNIQUE INDEX|DELETE FROM sqlite_sequence)/i', $line) || str_contains($line, 'sqlite_sequence')) {
        continue;
    }

    if (stripos($line, 'CREATE TABLE') === 0) {
        if (preg_match('/CREATE TABLE\s+"?([a-zA-Z0-9_]+)"?\s*\((.*)\)/is', $line, $m)) {
            $table = $m[1];
            $content = $m[2];
            $content = str_replace('"', '`', $content);
            $content = preg_replace('/`id` integer primary key autoincrement/i', '`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', $content);
            $content = preg_replace('/\binteger\b/i', 'INT(11)', $content);
            $content = preg_replace('/\bvarchar\b/i', 'VARCHAR(255)', $content);
            $content = preg_replace('/\bdatetime\b/i', 'DATETIME', $content);
            $content = preg_replace('/\btext\b/i', 'LONGTEXT', $content);
            $content = preg_replace('/\bnumeric\b/i', 'DECIMAL(19,4)', $content);
            $content = preg_replace('/\btinyint\(1\)\b/i', 'TINYINT(1)', $content);
            $content = preg_replace('/check\s*\([^)]+\)/i', '', $content);
            $content = preg_replace('/NOT NULL\s+NOT NULL/i', 'NOT NULL', $content);
            $content = preg_replace('/,\s*,/', ',', $content);
            $content = trim($content, ' ,');

            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= "CREATE TABLE `$table` ($content) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
        }
    } elseif (stripos($line, 'INSERT INTO') === 0) {
        // CAMBIO CLAVE: Usamos REPLACE INTO en lugar de INSERT INTO
        $line = preg_replace('/INSERT INTO/i', 'REPLACE INTO', $line);
        $line = str_replace('"', '`', $line);
        $output .= $line."\n";
    }
}

$output .= "\nSET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;";
file_put_contents($outputFile, $output);
echo "Archivo '{$outputFile}' generado con éxito.\n";
