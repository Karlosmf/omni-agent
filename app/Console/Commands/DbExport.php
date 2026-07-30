<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DbExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {file? : Nombre del archivo de salida} {--drop : Agregar sentencias DROP TABLE IF EXISTS}';

    protected $description = 'Genera un volcado (dump) de la base de datos actual.';

    public function handle()
    {
        $file = $this->argument('file');

        if (! $file) {
            $file = \Laravel\Prompts\text(
                label: '¿Cuál es el nombre del archivo de exportación?',
                placeholder: 'ej. backup_'.date('Y-m-d').'.sql',
                default: 'export_'.date('Y-m-d').'.sql',
                required: true
            );
        }

        if (! str_ends_with($file, '.sql')) {
            $file .= '.sql';
        }

        $addDrop = $this->option('drop');

        if (! $this->hasOption('drop') || ! $this->option('drop')) {
            if (! $this->input->hasParameterOption('--drop') && ! $this->input->hasParameterOption('--no-drop')) {
                $addDrop = \Laravel\Prompts\confirm(
                    label: '¿Querés incluir sentencias DROP TABLE IF EXISTS antes de crear las tablas?',
                    default: true,
                    yes: 'Sí',
                    no: 'No'
                );
            }
        }

        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->error('Este comando PHP puro solo soporta conexiones MySQL por el momento.');

            return self::FAILURE;
        }

        $filePath = base_path($file);
        \Laravel\Prompts\info("Iniciando exportación (vía PHP) a {$file}...");

        try {
            $tablesResult = DB::select('SHOW TABLES');
            $database = DB::getDatabaseName();
            $property = 'Tables_in_'.$database;

            $handle = fopen($filePath, 'w');

            // Header
            fwrite($handle, "-- Generado automáticamente con PHP (Artisan db:export)\n");
            fwrite($handle, '-- Fecha: '.date('Y-m-d H:i:s')."\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tablesResult as $tableRow) {
                // Ensure we get the correct table name regardless of casing/objects
                $tableName = null;
                foreach ($tableRow as $key => $value) {
                    $tableName = $value;
                    break;
                }

                \Laravel\Prompts\info("Exportando tabla: {$tableName}");

                if ($addDrop) {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                }

                $createTableResult = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createTableStmt = $createTableResult[0]->{'Create Table'};
                fwrite($handle, $createTableStmt.";\n\n");

                // Get data in chunks to avoid memory exhaustion
                DB::table($tableName)->orderByRaw('1')->chunk(500, function ($rows) use ($handle, $tableName) {
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array) $row as $val) {
                            if (is_null($val)) {
                                $values[] = 'NULL';
                            } else {
                                // Addslashes and quote
                                $escaped = addslashes((string) $val);
                                // Handle newlines
                                $escaped = str_replace(["\n", "\r"], ['\\n', '\\r'], $escaped);
                                $values[] = "'{$escaped}'";
                            }
                        }
                        $valuesStr = implode(', ', $values);
                        fwrite($handle, "INSERT INTO `{$tableName}` VALUES ({$valuesStr});\n");
                    }
                });

                fwrite($handle, "\n");
            }

            if (\Laravel\Prompts\confirm(
                label: '¿Deseas agregar el usuario admin por defecto (admin@admin.com / admin123) al archivo SQL?',
                default: true,
                yes: 'Sí',
                no: 'No'
            )) {
                $password = Hash::make('admin123');
                $date = now()->format('Y-m-d H:i:s');
                fwrite($handle, "\n-- Inyectando usuario admin por defecto\n");
                fwrite($handle, "DELETE FROM `users` WHERE `email` = 'admin@admin.com';\n");
                fwrite($handle, "INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES ('Admin', 'admin@admin.com', '{$password}', 'admin', '{$date}', '{$date}');\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            \Laravel\Prompts\info("✅ Exportación completada exitosamente en: {$file}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Ocurrió un error al exportar la base de datos con PHP:');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
