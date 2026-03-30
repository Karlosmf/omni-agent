<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSQLiteToMySQL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-sqlite-to-mysql {--truncate : Truncate MySQL tables before inserting data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from SQLite to MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration from SQLite to MySQL...');

        // Verify connections
        try {
            DB::connection('sqlite')->getPdo();
            $this->info('SQLite connection: OK');
            DB::connection('mysql')->getPdo();
            $this->info('MySQL connection: OK');
        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            return 1;
        }

        // Get all tables from SQLite
        $tables = DB::connection('sqlite')
            ->table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'not like', 'sqlite_%')
            ->pluck('name');

        if ($tables->isEmpty()) {
            $this->error('No tables found in SQLite database.');
            return 1;
        }

        // Disable foreign key checks on MySQL
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            $this->info("Migrating table: {$table}");

            if ($this->option('truncate')) {
                DB::connection('mysql')->table($table)->truncate();
            }

            // Copy data in chunks to avoid memory issues
            $totalCount = DB::connection('sqlite')->table($table)->count();
            $bar = $this->output->createProgressBar($totalCount);
            $bar->start();

            DB::connection('sqlite')->table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($table, $bar) {
                $data = array_map(function ($row) {
                    return (array) $row;
                }, $rows->toArray());

                DB::connection('mysql')->table($table)->insert($data);
                $bar->advance(count($data));
            });

            $bar->finish();
            $this->newLine();
        }

        // Re-enable foreign key checks
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Migration completed successfully!');
        
        return 0;
    }
}
