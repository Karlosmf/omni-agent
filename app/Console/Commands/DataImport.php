<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\text;

class DataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import {file? : El archivo SQL a importar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from a MySQL/SQL dump with dynamic table detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fileName = $this->argument('file');

        if (! $fileName) {
            $fileName = text(
                label: '¿Cuál es el nombre del archivo SQL a importar?',
                placeholder: 'source.sql',
                default: 'source.sql',
                required: true
            );
        }

        $filePath = base_path($fileName);

        while (! File::exists($filePath)) {
            $this->error("✘ Archivo no encontrado: $filePath");
            $fileName = text(
                label: 'Por favor, ingresa un nombre de archivo válido:',
                placeholder: 'source.sql',
                required: true
            );
            $filePath = base_path($fileName);
        }

        $this->newLine();
        $this->info("🚀 Iniciando migración universal desde: $fileName");
        $this->newLine();

        if (! $this->confirm('⚠️  Esto puede sobrescribir datos en las tablas detectadas. ¿Continuar?', true)) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        $chunksProcessed = 0;
        $errors = 0;
        $truncatedTables = [];

        $filesToProcess = [$filePath];

        $pathInfo = pathinfo($filePath);
        $basePattern = $pathInfo['dirname'].'/'.$pathInfo['filename'].'_*.sql';
        $extraFiles = File::glob($basePattern);
        if (! empty($extraFiles)) {
            sort($extraFiles);
            $filesToProcess = array_merge($filesToProcess, $extraFiles);
        }

        foreach ($filesToProcess as $currentFilePath) {
            $baseName = basename($currentFilePath);
            $isIncremental = (str_contains($baseName, '_'));

            $this->newLine();
            $this->warn("📄 Procesando: $baseName ".($isIncremental ? '[INCREMENTAL]' : '[BASE]'));
            $lastTableInFile = '';

            $handle = fopen($currentFilePath, 'r');
            if ($handle) {
                $buffer = '';
                $insideInsert = false;
                $currentTable = '';

                while (($line = fgets($handle)) !== false) {
                    $trimmedLine = trim($line);

                    if (! $insideInsert) {
                        if (stripos($trimmedLine, 'INSERT INTO') === 0 || stripos($trimmedLine, 'REPLACE INTO') === 0) {
                            if (preg_match('/(?:INSERT|REPLACE)\s+INTO\s+`?(\w+)`?/i', $trimmedLine, $matches)) {
                                $currentTable = $matches[1];

                                if (Schema::hasTable($currentTable)) {
                                    $insideInsert = true;
                                    $buffer = $line;

                                    // Convertir a REPLACE INTO si es incremental para evitar errores de duplicado
                                    if ($isIncremental) {
                                        $buffer = preg_replace('/INSERT\s+INTO/i', 'REPLACE INTO', $buffer);
                                    }

                                    // Truncar SOLO si es el archivo BASE y no se ha truncado aún en esta ejecución
                                    if (! $isIncremental && ! in_array($currentTable, $truncatedTables)) {
                                        DB::table($currentTable)->truncate();
                                        $truncatedTables[] = $currentTable;
                                    }

                                    if ($currentTable !== $lastTableInFile) {
                                        $this->newLine();
                                        $this->output->write("   ⚡ <comment>$currentTable</comment> ");
                                        $lastTableInFile = $currentTable;
                                    }
                                }
                            }
                        }
                    } else {
                        $buffer .= $line;
                    }

                    if ($insideInsert && str_ends_with($trimmedLine, ';')) {
                        $buffer = $this->filterNonExistentColumns($buffer, $currentTable);

                        try {
                            if (! empty(trim($buffer))) {
                                DB::unprepared($buffer);
                                $chunksProcessed++;
                                $this->output->write('<info>.</info>');
                            }
                        } catch (\Exception $e) {
                            $errors++;
                            $this->newLine();
                            $this->error("   ✘ Error en $currentTable: ".substr($e->getMessage(), 0, 150));
                        }

                        $buffer = '';
                        $insideInsert = false;
                    }
                }
                fclose($handle);
            }
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->newLine(2);
        $this->info('✨ Importación Finalizada.');
        $this->line("   ✅ Total bloques: <info>$chunksProcessed</info>");
        if ($errors > 0) {
            $this->line("   ⚠️  Errores: <error>$errors</error>");
        }
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Filters out non-existent columns from an INSERT/REPLACE statement based on the database schema.
     */
    private function filterNonExistentColumns(string $sql, string $tableName): string
    {
        $hasColumnNames = preg_match('/(?:INSERT|REPLACE)\s+INTO\s+`?'.$tableName.'`?\s*\((.*?)\)\s*VALUES/is', $sql, $colMatches);

        if (! $hasColumnNames) {
            return $sql;
        }

        $sqlColumns = array_map(function ($c) {
            return trim($c, " `\n\r\t");
        }, explode(',', $colMatches[1]));

        $dbColumns = Schema::getColumnListing($tableName);
        $dbColumnsSet = array_flip($dbColumns);

        $keptIndices = [];
        $keptColumns = [];
        foreach ($sqlColumns as $index => $col) {
            if (isset($dbColumnsSet[$col])) {
                $keptIndices[] = $index;
                $keptColumns[] = $col;
            }
        }

        if (count($keptColumns) === count($sqlColumns)) {
            return $sql;
        }

        if (empty($keptColumns)) {
            return '';
        }

        if (preg_match('/(?:VALUES)\s*(.*);/is', $sql, $matches)) {
            $valuesSection = $matches[1];
            preg_match_all('/\((.*?)\)(?:,|$)/s', $valuesSection, $rows);

            $newRows = [];
            foreach ($rows[1] as $row) {
                $parts = str_getcsv($row, ',', "'");

                $filteredParts = [];
                foreach ($keptIndices as $idx) {
                    $filteredParts[] = $parts[$idx] ?? null;
                }

                $newRows[] = $this->rebuildRow($filteredParts);
            }

            $verb = str_contains($sql, 'REPLACE') ? 'REPLACE' : 'INSERT';

            return "$verb INTO `$tableName` (`".implode('`, `', $keptColumns).'`) VALUES '.implode(',', $newRows).';';
        }

        return $sql;
    }

    /**
     * Rebuilds a CSV parsed array row into a valid SQL VALUES row part.
     */
    private function rebuildRow(array $parts): string
    {
        $sanitizedParts = array_map(function ($val) {
            $val = ($val !== null) ? trim($val) : null;
            if ($val === 'NULL' || $val === null || $val === '') {
                return 'NULL';
            }

            return "'".str_replace("'", "''", $val)."'";
        }, $parts);

        return '('.implode(',', $sanitizedParts).')';
    }
}
