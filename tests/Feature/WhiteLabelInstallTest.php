<?php

use App\Enums\Channel;
use App\Enums\UserRole;
use App\Models\AgencySetting;
use App\Models\ChannelAccount;
use App\Models\FinancialAccount;
use App\Models\User;
use App\Services\Meta\MetaCredentials;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('omni:install prepares a fresh install and can be repeated without duplicating anything', function () {
    config(['brand.name' => 'Viajes Sol']);

    $this->artisan('omni:install', ['--email' => 'dueña@sol.test', '--password' => 'clave-segura-1'])->assertSuccessful();
    $accounts = FinancialAccount::count();
    AgencySetting::query()->update(['company_name' => 'Cambiado en el panel']);

    $this->artisan('omni:install')->assertSuccessful();

    expect(User::where('role', UserRole::Admin)->count())->toBe(1)
        ->and(FinancialAccount::count())->toBe($accounts)->toBeGreaterThan(0)
        ->and(AgencySetting::count())->toBe(1)
        ->and(AgencySetting::first()->company_name)->toBe('Cambiado en el panel');
});

test('the first install takes the brand from the config', function () {
    config(['brand.name' => 'Viajes Sol', 'brand.email' => 'hola@sol.test']);

    $this->artisan('omni:install', ['--email' => 'a@sol.test', '--password' => 'clave-segura-1'])->assertSuccessful();

    expect(AgencySetting::first())->company_name->toBe('Viajes Sol')->contact_email->toBe('hola@sol.test');
});

test('omni:install refuses a weak admin password', function () {
    $this->artisan('omni:install', ['--email' => 'a@sol.test', '--password' => '123'])->assertFailed();

    expect(User::count())->toBe(0);
});

test('omni:check-config confirms each connected account with Meta and fails on a bad token', function () {
    Cache::forget('agency_settings');
    config(['services.whatsapp.graph_url' => 'https://graph.example.test', 'services.meta.instagram_graph_url' => 'https://ig.example.test', 'services.whatsapp.phone_number_id' => null, 'services.whatsapp.access_token' => null]);
    ChannelAccount::factory()->channel(Channel::WhatsApp)->create(['label' => 'Ventas', 'external_id' => 'PN1']);
    ChannelAccount::factory()->create(['label' => 'Insta', 'external_id' => 'IG1', 'auth_type' => 'instagram_login']);
    Http::fake([
        'graph.example.test/*/PN1*' => Http::response(['display_phone_number' => '+54 9 11 5555-0000']),
        'ig.example.test/*' => Http::response(['error' => ['message' => 'Token inválido']], 400),
    ]);

    $this->artisan('omni:check-config')
        ->expectsOutputToContain('+54 9 11 5555-0000')
        ->expectsOutputToContain('Token inválido')
        ->assertFailed();
});

test('the protected cron route runs the scheduler only with the right token', function () {
    $this->get('/cron/run?token=x')->assertNotFound();

    config(['omni-hosting.cron_token' => 'secreto-123']);

    $this->get('/cron/run?token=mal')->assertForbidden();
    $this->get('/cron/run')->assertForbidden();
    $this->post('/cron/run?token=secreto-123')->assertOk()->assertJson(['ok' => true]);
});

test('credentials come from the env first and from the connected accounts after', function () {
    config(['services.meta.app_secret' => 'del-env']);
    ChannelAccount::factory()->create(['app_secret' => 'de-la-cuenta']);

    expect(app(MetaCredentials::class)->webhookSecrets([Channel::Instagram]))->toBe(['del-env', 'de-la-cuenta'])
        ->and(app(MetaCredentials::class)->whatsappToken(ChannelAccount::factory()->channel(Channel::WhatsApp)->create(['access_token' => 'tk'])))->toBe('tk');
});

test('omni:install still creates the admin when the hosting does not allow the storage link', function () {
    $public = sys_get_temp_dir().'/omni-public-'.uniqid();
    mkdir($public);
    app()->usePublicPath($public);
    Artisan::command('storage:link', fn () => throw new Error('Call to undefined function exec()'));

    $this->artisan('omni:install', ['--email' => 'admin@sol.test', '--password' => 'clave-segura-1'])
        ->expectsOutputToContain('No se pudo crear el enlace de storage')
        ->assertSuccessful();

    expect(User::where('email', 'admin@sol.test')->value('role'))->toBe(UserRole::Admin);

    rmdir($public);
});
