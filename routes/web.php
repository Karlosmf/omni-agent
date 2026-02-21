<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/chat', 'public.chat-assistant')->name('chat');

Volt::route('/paquetes', 'pages.packages.index')->name('packages.index');

Volt::route('/paquetes/{slug}', 'pages.packages.show')->name('packages.show');

Route::get('/migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        return '<h1>Migraciones completadas</h1><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre><a href="/">Volver al inicio</a>';
    } catch (\Exception $e) {
        return '<h1>Error en la migración</h1><pre>' . $e->getMessage() . '</pre>';
    }
});

Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
    ->name('transactions.receipt');
