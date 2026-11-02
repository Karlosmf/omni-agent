<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\MetaSocialWebhookController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\ZernioWebhookController;
use App\Livewire\PublicCotizador;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/quienes-somos', 'pages.quienes-somos')->name('pages.quienes-somos');
Route::view('/politicas-de-privacidad', 'pages.privacidad')->name('pages.privacidad');
Route::view('/politicas-de-cookies', 'pages.cookies')->name('pages.cookies');

Volt::route('/chat', 'public.chat-assistant')->name('chat');

Volt::route('/paquetes', 'pages.packages.index')->name('packages.index');

Volt::route('/paquetes/{slug}', 'pages.packages.show')->name('packages.show');

// Cotizador Público
Route::get('/cotizar', PublicCotizador::class)->name('cotizador');

// Landing pública del presupuesto — sin autenticación, accesible con el link
Route::get('/presupuesto/{token}', [PublicBookingController::class, 'show'])->middleware('throttle:60,1')->name('booking.public');

// Portal del Cliente is handled by Filament at /portal

Route::middleware('auth')->group(function () {
    Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
        ->name('transactions.receipt');
});

// WhatsApp Cloud API webhook (no CSRF: authenticated by Meta's HMAC signature).
Route::match(['get', 'post'], '/cron/run', CronController::class)->middleware('throttle:30,1')->name('cron.run');

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::get('/webhooks/meta/{token?}', [MetaSocialWebhookController::class, 'verify']);
Route::post('/webhooks/meta/{token?}', [MetaSocialWebhookController::class, 'receive'])->middleware('throttle:300,1')->name('webhooks.meta');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:300,1')->name('webhooks.whatsapp');
Route::post('/webhooks/zernio', [ZernioWebhookController::class, 'receive'])->middleware('throttle:300,1')->name('webhooks.zernio');
