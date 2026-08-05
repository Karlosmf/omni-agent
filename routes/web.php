<?php

use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReceiptController;
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
Route::get('/presupuesto/{token}', [PublicBookingController::class, 'show'])->name('booking.public');

// Portal del Cliente
Route::redirect('/portal', '/portal/dashboard');
Volt::route('/portal/login', 'portal.login')->name('login')->middleware('guest');
Volt::route('/portal/dashboard', 'portal.dashboard')->name('portal.dashboard')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
        ->name('transactions.receipt');
});
