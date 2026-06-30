<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('it imports data from sql dump file', function () {
    $tempFile = base_path('temp_test_import.sql');

    // Create a temporary SQL dump targeting the users table
    $sqlContent = <<<'SQL'
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`) VALUES
(999, 'Imported User', 'imported@example.com', NULL, 'secret', NULL, '2026-06-30 18:00:00', '2026-06-30 18:00:00', 'admin');
SQL;

    File::put($tempFile, $sqlContent);

    // Call the command and expect success exit code
    $this->artisan('db:import', ['file' => 'temp_test_import.sql'])
        ->expectsConfirmation('⚠️  Esto puede sobrescribir datos en las tablas detectadas. ¿Continuar?', 'yes')
        ->assertExitCode(0);

    // Assert the user was imported
    $this->assertDatabaseHas('users', [
        'id' => 999,
        'email' => 'imported@example.com',
        'name' => 'Imported User',
    ]);

    // Clean up
    if (File::exists($tempFile)) {
        File::delete($tempFile);
    }
});

test('it prompts for filename if not provided and source.sql does not exist', function () {
    $tempFile = base_path('temp_test_import_prompt.sql');

    $sqlContent = <<<'SQL'
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`) VALUES
(998, 'Prompted User', 'prompted@example.com', NULL, 'secret', NULL, '2026-06-30 18:00:00', '2026-06-30 18:00:00', 'admin');
SQL;

    File::put($tempFile, $sqlContent);

    // Ensure source.sql doesn't exist during test
    $sourcePath = base_path('source.sql');
    $sourceBackupPath = base_path('source.sql.bak');
    $hasBackup = false;
    if (File::exists($sourcePath)) {
        File::move($sourcePath, $sourceBackupPath);
        $hasBackup = true;
    }

    $this->artisan('db:import')
        ->expectsQuestion('¿Cuál es el nombre del archivo SQL a importar?', 'temp_test_import_prompt.sql')
        ->expectsConfirmation('⚠️  Esto puede sobrescribir datos en las tablas detectadas. ¿Continuar?', 'yes')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'id' => 998,
        'email' => 'prompted@example.com',
    ]);

    // Restore backup if any
    if ($hasBackup) {
        File::move($sourceBackupPath, $sourcePath);
    }

    if (File::exists($tempFile)) {
        File::delete($tempFile);
    }
});
