<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OmniInstall extends Command
{
    protected $signature = 'omni:install {--name=Administrador} {--email=} {--password=}';

    protected $description = 'Prepara una instalación nueva: tablas, datos base, enlace de storage y usuario administrador. Se puede repetir sin riesgo.';

    public function handle(): int
    {
        $this->components->task('Migraciones', fn () => $this->callSilently('migrate', ['--force' => true]) === self::SUCCESS);
        $this->components->task('Datos base (marca, servicios, cajas, categorías)', fn () => $this->callSilently('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) === self::SUCCESS);

        if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
            $this->components->task('Enlace de storage', fn () => $this->linkStorage());
        }

        return $this->ensureAdmin();
    }

    /**
     * Some shared hostings (Hostinger) disable both symlink() and exec(): the link then has to be made by hand,
     * and the rest of the installation must still run.
     */
    private function linkStorage(): bool
    {
        try {
            if ($this->callSilently('storage:link') === self::SUCCESS && is_link(public_path('storage'))) {
                return true;
            }
        } catch (\Throwable) {
            // Handled below with the manual command.
        }

        $this->components->warn('No se pudo crear el enlace de storage (el hosting no lo permite desde PHP). Crealo a mano por SSH o desde el administrador de archivos: '
            .'ln -s '.storage_path('app/public').' '.public_path('storage'));

        return false;
    }

    /**
     * Crea el primer administrador. Si ya hay uno y no se pidió otro, no toca nada.
     */
    private function ensureAdmin(): int
    {
        $email = $this->option('email');

        if (blank($email) && User::where('role', UserRole::Admin)->exists()) {
            $this->components->info('Ya hay un administrador: no se creó otro.');

            return self::SUCCESS;
        }

        $email ??= $this->ask('Email del administrador');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        $validator = Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email', 'password' => 'required|min:8']);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        // `role` no es asignable en masa; el usuario existente conserva su rol y solo cambia la clave.
        User::unguarded(fn () => User::updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('name'), 'password' => Hash::make($password), 'role' => UserRole::Admin],
        ));

        $this->components->info("Administrador listo: {$email}");

        return self::SUCCESS;
    }
}
