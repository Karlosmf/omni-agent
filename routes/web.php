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

Route::middleware('auth')->group(function () {
    Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
        ->name('transactions.receipt');
});
