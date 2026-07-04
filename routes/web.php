<?php

use App\Http\Controllers\ReceiptController;
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

Route::middleware('auth')->group(function () {
    Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
        ->name('transactions.receipt');
});
