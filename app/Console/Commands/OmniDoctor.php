<?php

namespace App\Console\Commands;

use App\Enums\RateSource;
use App\Services\CurrencyService;
use App\Support\CronHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OmniDoctor extends Command
{
    protected $signature = 'omni:doctor';

    protected $description = 'Verifica que la instalación esté lista (PHP, permisos, base de datos, cron, cola y cotizaciones).';

    public function handle(CurrencyService $currencies): int
    {
        $checks = [
            'PHP >= 8.4' => [version_compare(PHP_VERSION, '8.4.0', '>='), 'Versión actual: '.PHP_VERSION.'. Cambiala desde el panel del hosting.'],
            'Extensiones PHP' => $this->extensions(),
            'storage/ escribible' => [is_writable(storage_path()), 'Dale permisos de escritura a storage/ y bootstrap/cache/.'],
            'bootstrap/cache/ escribible' => [is_writable(base_path('bootstrap/cache')), 'Dale permisos de escritura a bootstrap/cache/.'],
            'Base de datos' => $this->database(),
            'storage:link' => [is_link(public_path('storage')) || is_dir(public_path('storage')), 'Ejecutá "storage:link" desde Sistema → Server Admin.'],
            'Cola en base de datos' => [config('queue.default') === 'database', 'Poné QUEUE_CONNECTION=database en el .env (en local puede ser sync).'],
            'Cron activo' => $this->cron(),
            'Trabajos fallidos' => $this->failedJobs(),
            'Cotización del dólar' => [$currencies->getDollarRate(RateSource::Oficial) !== null, 'No hay cotización guardada ni DolarAPI responde: se tendrá que cargar a mano.'],
        ];

        $failed = 0;
        foreach ($checks as $name => [$ok, $hint]) {
            $ok ? $this->line("  <info>✔</info> {$name}") : $this->line("  <error>✘</error> {$name} — {$hint}");
            $failed += $ok ? 0 : 1;
        }

        $this->newLine();
        $failed === 0 ? $this->info('Todo en orden.') : $this->warn("{$failed} verificación(es) con problemas.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    protected function extensions(): array
    {
        $missing = array_filter(['mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'zip', 'curl'], fn (string $ext) => ! extension_loaded($ext));

        return [$missing === [], 'Faltan: '.implode(', ', $missing)];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    protected function database(): array
    {
        try {
            DB::connection()->getPdo();

            return [true, ''];
        } catch (\Throwable $e) {
            return [false, 'No se puede conectar: '.$e->getMessage()];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    protected function cron(): array
    {
        $profile = config('omni-hosting.profiles.hostinger');
        $example = str_replace(['{path}', '{php}'], [base_path(), $profile['php_binary']], config('omni-hosting.cron_command'));

        return [! CronHeartbeat::isStale(), 'No hay latido en los últimos '.CronHeartbeat::STALE_AFTER_MINUTES." minutos. Creá este cron en el panel del hosting: {$example}"];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    protected function failedJobs(): array
    {
        try {
            $count = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return [false, 'No existe la tabla failed_jobs: corré las migraciones.'];
        }

        return [$count === 0, "{$count} trabajo(s) fallido(s). Revisá con: php artisan queue:failed"];
    }
}
