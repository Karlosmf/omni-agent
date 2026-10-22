<?php

namespace App\Console\Commands;

use App\Models\ChannelAccount;
use App\Services\Meta\MetaConnectionCheck;
use App\Services\Meta\MetaCredentials;
use Illuminate\Console\Command;

class OmniCheckConfig extends Command
{
    protected $signature = 'omni:check-config';

    protected $description = 'Revisa que el .env de esta instalación esté completo y que las credenciales de Meta funcionen.';

    public function handle(MetaConnectionCheck $meta, MetaCredentials $credentials): int
    {
        $production = app()->environment('production');

        $checks = [
            'APP_KEY' => [filled(config('app.key')), 'Corré «php artisan key:generate».'],
            'APP_URL' => [filled(config('app.url')) && (! $production || ! str_contains((string) config('app.url'), 'localhost')), 'Poné la dirección real del sitio (https://…).'],
            'Marca (BRAND_NAME)' => [(get_agency_settings()?->company_name ?? config('brand.name')) !== 'Mi Agencia', 'Completá BRAND_NAME en el .env o el nombre en Ajustes de la agencia.'],
            'Correo (MAIL_MAILER)' => [! $production || config('mail.default') !== 'log', 'Con MAIL_MAILER=log no sale ningún email. Configurá SMTP.'],
            'Cron sin servidor (CRON_TOKEN)' => [filled(config('omni-hosting.cron_token')), 'Opcional: sin cron del hosting, definí CRON_TOKEN y llamá a /cron/run?token=… desde un servicio externo.', true],
        ];

        foreach ($this->metaChecks($meta, $credentials) as $name => $result) {
            $checks[$name] = $result;
        }

        $failed = 0;

        foreach ($checks as $name => $check) {
            [$ok, $hint] = $check;
            $optional = $check[2] ?? false;

            if ($ok) {
                $this->line("  <info>✔</info> {$name}".($hint !== '' && str_starts_with($name, 'Meta') ? " — {$hint}" : ''));

                continue;
            }

            $this->line(($optional ? '  <comment>•</comment> ' : '  <error>✘</error> ')."{$name} — {$hint}");
            $failed += $optional ? 0 : 1;
        }

        $this->newLine();
        $failed === 0 ? $this->info('Configuración lista.') : $this->warn("{$failed} punto(s) por resolver.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<string, array{0: bool, 1: string}>
     */
    private function metaChecks(MetaConnectionCheck $meta, MetaCredentials $credentials): array
    {
        $checks = [];

        if (filled($credentials->whatsappPhoneNumberId()) || filled($credentials->whatsappToken())) {
            $checks['Meta · WhatsApp (.env)'] = $meta->whatsappFromEnv();
        }

        foreach (ChannelAccount::where('is_active', true)->get() as $account) {
            $checks["Meta · {$account->channel->getLabel()} «{$account->label}»"] = $meta->account($account);
        }

        if ($checks === []) {
            $checks['Meta · canales'] = [true, 'Todavía no hay cuentas conectadas (Sistema → Canales de mensajería).'];
        }

        return $checks;
    }
}
