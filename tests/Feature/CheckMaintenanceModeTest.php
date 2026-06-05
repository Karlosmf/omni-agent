<?php

use App\Models\AgencySetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('agency_settings');
    AgencySetting::truncate();
    Artisan::call('up');
});

afterEach(function () {
    Artisan::call('up');
});

test('visitors can view site when maintenance mode is disabled', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});

test('visitors see maintenance page when maintenance mode is enabled', function () {
    Artisan::call('down');

    $response = $this->get('/');
    $response->assertStatus(503);
    $response->assertSee('Estamos en mantenimiento');
});

test('admin path is bypassed during maintenance mode', function () {
    Artisan::call('down');

    // Admin path remains accessible (either redirect to login or login page)
    $response = $this->get('/admin');
    $response->assertStatus(302); // redirects to admin/login
});

test('visitors can bypass maintenance mode using a bypass key', function () {
    $this->disableCookieEncryption();

    Artisan::call('down', ['--secret' => 'secret123']);

    // 1. Visit with wrong key/path
    $response = $this->get('/wrong');
    $response->assertStatus(503);

    // 2. Visit with correct secret key path
    $response = $this->get('/secret123');
    $response->assertStatus(302);
    $response->assertRedirect('/');

    // Get the cookie from the response
    $cookies = $response->headers->getCookies();
    $maintenanceCookie = collect($cookies)->first(fn ($cookie) => $cookie->getName() === 'laravel_maintenance');

    expect($maintenanceCookie)->not->toBeNull();

    // 3. Subsequent request to root with the cookie returns 200
    $response = $this->withCookie($maintenanceCookie->getName(), $maintenanceCookie->getValue())->get('/');
    $response->assertStatus(200);
});
