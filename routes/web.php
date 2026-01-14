<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/chat', 'public.chat-assistant')->name('chat');

Route::get('/admin/transactions/{transaction}/receipt', [ReceiptController::class, 'download'])
    ->name('transactions.receipt');
