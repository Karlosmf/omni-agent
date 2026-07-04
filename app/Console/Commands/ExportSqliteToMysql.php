<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class ExportSqliteToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-sql 
                            {output? : El nombre del archivo SQL de salida}
                            {--schema : Exportar estructura de tablas (CREATE TABLE)}
                            {--data : Exportar datos de tablas (INSERT INTO)}
                            {--truncate : Truncar las tablas en el destino antes de insertar los datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un archivo SQL para migrar de SQLite a MySQL con opciones de estructura, datos y truncado';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputFile = $this->argument('output');

        if (! $outputFile) {
            $outputFile = text(
                label: '¿Cuál es el nombre del archivo SQL de salida?',
                default: 'migration_to_mysql.sql'
            );
        }

        $exportSchema = $this->option('schema');
        $exportData = $this->option('data');
        $truncate = $this->option('truncate');

        // Modo interactivo si no se pasó ni --schema ni --data
        if (! $exportSchema && ! $exportData) {
            $this->info('Modo Interactivo de Exportación');
            $exportSchema = confirm('¿Deseas exportar la ESTRUCTURA de las tablas (CREATE TABLE)?', true);
            $exportData = confirm('¿Deseas exportar los DATOS de las tablas (INSERT INTO)?', true);
        }

        if (! $exportSchema && ! $exportData) {
            $this->warn('No seleccionaste nada para exportar. Operación cancelada.');

            return self::SUCCESS;
        }

        // Si exporta datos y no se pasó el flag explícito, preguntamos por el truncate
        if ($exportData && ! $truncate && ! in_array('--truncate', $_SERVER['argv'] ?? [])) {
            $truncate = confirm('¿Deseas incluir sentencias TRUNCATE para vaciar las tablas antes de insertar?', false);
        }

        $this->info("\nResumen: Exportando ".($exportSchema ? 'estructura ' : '').($exportSchema && $exportData ? 'y ' : '').($exportData ? 'datos ' : '')."de SQLite a {$outputFile}...");

        $tables = Schema::connection('sqlite')->getTables();
        $sql = "-- Migración de SQLite a MySQL\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableInfo) {
            $tableName = $tableInfo['name'];

            if (str_starts_with($tableName, 'sqlite_')) {
                continue;
            }

            $this->comment("Procesando tabla: {$tableName}");

            if ($exportSchema) {
                $sql .= "-- Estructura para la tabla: {$tableName}\n";
                $sql .= $this->generateCreateTableSql($tableName);
                $sql .= "\n\n";
            }

            if ($exportData) {
                $sql .= "-- Datos para la tabla: {$tableName}\n";

                if ($truncate) {
                    $sql .= "TRUNCATE TABLE `{$tableName}`;\n";
                }

                $columns = Schema::connection('sqlite')->getColumnListing($tableName);
                $quotedColumns = array_map(fn ($col) => "`$col`", $columns);
                $columnsStr = implode(', ', $quotedColumns);

                $orderColumn = in_array('id', $columns) ? 'id' : ($columns[0] ?? null);

                if ($orderColumn) {
                    DB::connection('sqlite')->table($tableName)->orderBy($orderColumn)->chunk(100, function ($rows) use (&$sql, $tableName, $columnsStr) {
                        foreach ($rows as $row) {
                            $values = [];
                            foreach ($row as $value) {
                                $values[] = $this->formatValue($value);
                            }
                            $valuesStr = implode(', ', $values);
                            $sql .= "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES ({$valuesStr});\n";
                        }
                    });
                } else {
                    $rows = DB::connection('sqlite')->table($tableName)->get();
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($row as $value) {
                            $values[] = $this->formatValue($value);
                        }
                        $valuesStr = implode(', ', $values);
                        $sql .= "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES ({$valuesStr});\n";
                    }
                }

                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($outputFile, $sql);

        $this->info("¡Exportación completada! Archivo generado: {$outputFile}");

        return self::SUCCESS;
    }

    /**
     * Genera una sentencia CREATE TABLE IF NOT EXISTS compatible con MySQL basada en SQLite.
     */
    protected function generateCreateTableSql(string $tableName): string
    {
        $columns = Schema::connection('sqlite')->getColumns($tableName);
        $lines = [];
        $primaryKeys = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type_name'] ?: $column['type'];
            $nullable = $column['nullable'] ? 'NULL' : 'NOT NULL';
            $autoIncrement = $column['auto_increment'] ? 'AUTO_INCREMENT' : '';
            $default = '';

            if ($column['default'] !== null) {
                $default = 'DEFAULT '.$column['default'];
            }

            // Mapeo de tipos asegurando BIGINT UNSIGNED para todos los enteros
            // Esto garantiza que los Foreign Keys siempre coincidan con las Primary Keys de Laravel
            $mysqlType = match (strtolower($type)) {
                'integer', 'int' => 'BIGINT UNSIGNED',
                'varchar', 'string' => 'VARCHAR(255)',
                'text' => 'TEXT',
                'datetime', 'timestamp' => 'DATETIME',
                'date' => 'DATE',
                'numeric', 'decimal', 'float', 'double' => 'DOUBLE',
                'boolean', 'tinyint' => 'TINYINT',
                default => 'VARCHAR(255)',
            };

            if ($column['auto_increment']) {
                $primaryKeys[] = "`$name`";
            }

            $lines[] = "    `$name` $mysqlType $nullable $autoIncrement $default";
        }

        if (empty($primaryKeys)) {
            $indexes = Schema::connection('sqlite')->getIndexes($tableName);
            foreach ($indexes as $index) {
                if ($index['primary']) {
                    foreach ($index['columns'] as $col) {
                        $primaryKeys[] = "`$col`";
                    }
                }
            }
        }

        if (! empty($primaryKeys)) {
            $lines[] = '    PRIMARY KEY ('.implode(', ', $primaryKeys).')';
        }

        $foreignKeys = Schema::connection('sqlite')->getForeignKeys($tableName);
        foreach ($foreignKeys as $fk) {
            $localCols = implode('`, `', $fk['columns']);
            $foreignCols = implode('`, `', $fk['foreign_columns']);
            $onDelete = $fk['on_delete'] ? "ON DELETE {$fk['on_delete']}" : '';
            $onUpdate = $fk['on_update'] ? "ON UPDATE {$fk['on_update']}" : '';

            $lines[] = "    FOREIGN KEY (`$localCols`) REFERENCES `{$fk['foreign_table']}` (`$foreignCols`) $onDelete $onUpdate";
        }

        return "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n".implode(",\n", $lines)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    /**
     * Formatea el valor para la sentencia SQL.
     */
    protected function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_numeric($value) && ! is_string($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $escaped = str_replace(
            ['\\', "'", "\0", "\n", "\r", "\x1a"],
            ['\\\\', "''", '\\0', '\\n', '\\r', '\\Z'],
            (string) $value
        );

        return "'".$escaped."'";
    }
}
